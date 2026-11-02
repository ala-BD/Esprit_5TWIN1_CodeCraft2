<?php

namespace Tests\Unit;

use App\Services\DonationImageHashService;
use PHPUnit\Framework\TestCase;

class DonationImageHashServiceTest extends TestCase
{
    public function test_perceptual_hash_matches_resized_compressed_and_cropped_photos(): void
    {
        $paths = [];
        $original = imagecreatetruecolor(300, 300);
        $background = imagecolorallocate($original, 245, 245, 245);
        $fabric = imagecolorallocate($original, 28, 73, 132);
        imagefill($original, 0, 0, $background);
        imagefilledpolygon($original, [90, 75, 210, 75, 225, 225, 75, 225], $fabric);
        imagefilledpolygon($original, [90, 85, 45, 145, 65, 160, 105, 125], $fabric);
        imagefilledpolygon($original, [210, 85, 255, 145, 235, 160, 195, 125], $fabric);
        $originalPath = tempnam(sys_get_temp_dir(), 'donation-hash-original-');
        imagejpeg($original, $originalPath, 92);
        $paths[] = $originalPath;

        $source = imagecreatefromjpeg($originalPath);
        $resized = imagecreatetruecolor(160, 160);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, 160, 160, 300, 300);
        $resizedPath = tempnam(sys_get_temp_dir(), 'donation-hash-resized-');
        imagejpeg($resized, $resizedPath, 48);
        $paths[] = $resizedPath;

        $cropped = imagecreatetruecolor(250, 250);
        imagecopy($cropped, $source, 0, 0, 25, 25, 250, 250);
        $croppedPath = tempnam(sys_get_temp_dir(), 'donation-hash-cropped-');
        imagejpeg($cropped, $croppedPath, 75);
        $paths[] = $croppedPath;

        try {
            $service = new DonationImageHashService();
            $originalHashes = $service->hashes($originalPath);

            $this->assertLessThanOrEqual(10, $service->distance($originalHashes, $service->hashes($resizedPath)));
            $this->assertLessThanOrEqual(10, $service->distance($originalHashes, $service->hashes($croppedPath)));
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }
}
