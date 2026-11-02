<?php

namespace App\Services;

use RuntimeException;

class DonationImageHashService
{
    /**
     * @return list<string>
     */
    public function hashes(string $path): array
    {
        $contents = file_get_contents($path);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if ($image === false) {
            throw new RuntimeException('Impossible de lire la photo pour la détection de doublons.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);
        $hashes = [];

        foreach ([1.0, 0.85, 0.7] as $scale) {
            $cropSide = max(1, (int) round($side * $scale));
            $sourceX = (int) (($width - $cropSide) / 2);
            $sourceY = (int) (($height - $cropSide) / 2);
            $sample = imagecreatetruecolor(32, 32);
            imagecopyresampled($sample, $image, 0, 0, $sourceX, $sourceY, 32, 32, $cropSide, $cropSide);
            $hashes[] = $this->perceptualHash($sample);
        }

        return array_values(array_unique($hashes));
    }

    /**
     * Returns the lowest Hamming distance between two sets of perceptual hashes.
     *
     * @param list<string> $first
     * @param list<string> $second
     */
    public function distance(array $first, array $second): ?int
    {
        if ($first === [] || $second === []) {
            return null;
        }

        $minimum = PHP_INT_MAX;
        foreach ($first as $firstHash) {
            foreach ($second as $secondHash) {
                if (!preg_match('/^[a-f0-9]{16}$/i', $firstHash) || !preg_match('/^[a-f0-9]{16}$/i', $secondHash)) {
                    continue;
                }

                $distance = 0;
                for ($index = 0; $index < 16; $index++) {
                    $distance += substr_count(decbin(hexdec($firstHash[$index]) ^ hexdec($secondHash[$index])), '1');
                }
                $minimum = min($minimum, $distance);
            }
        }

        return $minimum === PHP_INT_MAX ? null : $minimum;
    }

    private function perceptualHash(\GdImage $image): string
    {
        $pixels = [];
        for ($y = 0; $y < 32; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $color = imagecolorat($image, $x, $y);
                $red = ($color >> 16) & 0xff;
                $green = ($color >> 8) & 0xff;
                $blue = $color & 0xff;
                $pixels[$y][$x] = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);
            }
        }

        $coefficients = [];
        for ($v = 0; $v < 8; $v++) {
            for ($u = 0; $u < 8; $u++) {
                $coefficient = 0.0;
                for ($y = 0; $y < 32; $y++) {
                    for ($x = 0; $x < 32; $x++) {
                        $coefficient += $pixels[$y][$x]
                            * cos(((2 * $x + 1) * $u * M_PI) / 64)
                            * cos(((2 * $y + 1) * $v * M_PI) / 64);
                    }
                }
                $coefficients[] = $coefficient;
            }
        }

        $frequencies = array_slice($coefficients, 1);
        sort($frequencies);
        $median = $frequencies[(int) floor(count($frequencies) / 2)];
        $bits = '';
        foreach ($coefficients as $coefficient) {
            $bits .= $coefficient >= $median ? '1' : '0';
        }

        $hash = '';
        for ($index = 0; $index < 64; $index += 4) {
            $hash .= dechex(bindec(substr($bits, $index, 4)));
        }

        return $hash;
    }
}
