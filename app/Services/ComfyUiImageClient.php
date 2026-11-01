<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ComfyUiImageClient
{
    /**
     * @return array{mime_type: string, data: string}
     */
    public function generer(string $instruction, string $imagePath): array
    {
        $baseUrl = rtrim((string) config('services.donation_images.comfyui.url'), '/');
        $timeout = max(30, (int) config('services.donation_images.comfyui.timeout', 180));
        set_time_limit($timeout + 15);

        $preparedPath = $this->prepareImage($imagePath);
        try {
            $uploadedImage = $this->uploadImage($baseUrl, $preparedPath);
            $promptId = $this->submitWorkflow($baseUrl, $uploadedImage, $instruction);
            $image = $this->waitForImage($baseUrl, $promptId, $timeout);
        } finally {
            @unlink($preparedPath);
        }

        return [
            'mime_type' => $image->header('Content-Type'),
            'data' => $image->body(),
        ];
    }

    public function estConfigure(): bool
    {
        return filled(config('services.donation_images.comfyui.url'))
            && filled(config('services.donation_images.comfyui.checkpoint'));
    }

    /**
     * @return array{name: string, subfolder: string}
     */
    private function uploadImage(string $baseUrl, string $imagePath): array
    {
        $contents = file_get_contents($imagePath);
        if ($contents === false) {
            throw new RuntimeException('Impossible de lire la photo à envoyer à ComfyUI.');
        }

        $extension = match (mime_content_type($imagePath)) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'jpg',
        };
        $filename = 'textilecycle-' . Str::uuid() . '.' . $extension;
        $response = Http::timeout(15)
            ->attach('image', $contents, $filename)
            ->post($baseUrl . '/upload/image', [
                'type' => 'input',
                'overwrite' => 'true',
            ]);
        $this->assertSuccessful($response, 'upload');

        $name = $response->json('name');
        if (!is_string($name) || $name === '') {
            throw new RuntimeException('ComfyUI n’a pas retourné le nom de la photo envoyée.');
        }

        return [
            'name' => $name,
            'subfolder' => is_string($response->json('subfolder')) ? $response->json('subfolder') : '',
        ];
    }

    private function prepareImage(string $imagePath): string
    {
        $contents = file_get_contents($imagePath);
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if ($source === false) {
            throw new RuntimeException('Impossible de préparer la photo pour le modèle local.');
        }

        $scale = min(1, 768 / max(imagesx($source), imagesy($source)));
        $width = max(64, (int) round((imagesx($source) * $scale) / 8) * 8);
        $height = max(64, (int) round((imagesy($source) * $scale) / 8) * 8);
        $prepared = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($prepared, 255, 255, 255);
        imagefill($prepared, 0, 0, $background);
        imagecopyresampled($prepared, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        $temporaryPath = tempnam(sys_get_temp_dir(), 'textilecycle-comfyui-');
        if ($temporaryPath === false || !imagejpeg($prepared, $temporaryPath, 88)) {
            if ($temporaryPath !== false) {
                @unlink($temporaryPath);
            }
            throw new RuntimeException('Impossible d’enregistrer la photo préparée pour le modèle local.');
        }

        return $temporaryPath;
    }

    /**
     * @param array{name: string, subfolder: string} $uploadedImage
     */
    private function submitWorkflow(string $baseUrl, array $uploadedImage, string $instruction): string
    {
        $workflow = $this->workflow($uploadedImage, $instruction);
        $response = Http::timeout(15)->post($baseUrl . '/prompt', [
            'prompt' => $workflow,
            'client_id' => (string) Str::uuid(),
        ]);
        $this->assertSuccessful($response, 'prompt');

        $promptId = $response->json('prompt_id');
        if (!is_string($promptId) || $promptId === '') {
            $error = $response->json('error.message') ?? $response->json('node_errors');
            throw new RuntimeException('ComfyUI n’a pas accepté le workflow : ' . json_encode($error));
        }

        return $promptId;
    }

    /**
     * @return array<string, array{class_type: string, inputs: array<string, mixed>}>
     */
    private function workflow(array $uploadedImage, string $instruction): array
    {
        $imageName = $uploadedImage['subfolder'] !== ''
            ? $uploadedImage['subfolder'] . '/' . $uploadedImage['name']
            : $uploadedImage['name'];
        $steps = max(1, min(50, (int) config('services.donation_images.comfyui.steps', 20)));
        $cfg = max(1, min(12, (float) config('services.donation_images.comfyui.cfg', 6.5)));
        $denoise = max(0.1, min(0.9, (float) config('services.donation_images.comfyui.denoise', 0.5)));
        $filenamePrefix = 'textilecycle/' . Str::uuid();

        return [
            '1' => [
                'class_type' => 'CheckpointLoaderSimple',
                'inputs' => ['ckpt_name' => config('services.donation_images.comfyui.checkpoint')],
            ],
            '2' => [
                'class_type' => 'LoadImage',
                'inputs' => ['image' => $imageName],
            ],
            '3' => [
                'class_type' => 'CLIPTextEncode',
                'inputs' => [
                    'text' => $instruction,
                    'clip' => ['1', 1],
                ],
            ],
            '4' => [
                'class_type' => 'CLIPTextEncode',
                'inputs' => [
                    'text' => 'person, human, face, hands, mannequin, model, background clutter, extra garments, '
                        . 'accessories, watermark, blurry, deformed',
                    'clip' => ['1', 1],
                ],
            ],
            '5' => [
                'class_type' => 'VAEEncode',
                'inputs' => [
                    'pixels' => ['2', 0],
                    'vae' => ['1', 2],
                ],
            ],
            '6' => [
                'class_type' => 'KSampler',
                'inputs' => [
                    'seed' => random_int(0, PHP_INT_MAX),
                    'steps' => $steps,
                    'cfg' => $cfg,
                    'sampler_name' => 'euler',
                    'scheduler' => 'normal',
                    'denoise' => $denoise,
                    'model' => ['1', 0],
                    'positive' => ['3', 0],
                    'negative' => ['4', 0],
                    'latent_image' => ['5', 0],
                ],
            ],
            '7' => [
                'class_type' => 'VAEDecode',
                'inputs' => [
                    'samples' => ['6', 0],
                    'vae' => ['1', 2],
                ],
            ],
            '8' => [
                'class_type' => 'SaveImage',
                'inputs' => [
                    'filename_prefix' => $filenamePrefix,
                    'images' => ['7', 0],
                ],
            ],
        ];
    }

    private function waitForImage(string $baseUrl, string $promptId, int $timeout): Response
    {
        $deadline = microtime(true) + $timeout;
        do {
            $response = Http::timeout(15)->get($baseUrl . '/history/' . rawurlencode($promptId));
            $this->assertSuccessful($response, 'history');
            $history = $response->json($promptId);

            if (is_array($history)) {
                $status = $history['status']['status_str'] ?? null;
                if ($status === 'error') {
                    throw new RuntimeException('ComfyUI a échoué pendant la génération de l’image.');
                }

                $output = $history['outputs']['8']['images'][0] ?? null;
                if (is_array($output) && !empty($output['filename'])) {
                    return $this->downloadImage($baseUrl, $output);
                }
                if (($history['status']['completed'] ?? false) === true) {
                    throw new RuntimeException('ComfyUI a terminé sans produire de photo.');
                }
            }

            if (microtime(true) < $deadline) {
                sleep(1);
            }
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Délai dépassé pendant la génération locale de l’image.');
    }

    private function downloadImage(string $baseUrl, array $output): Response
    {
        $response = Http::timeout(15)->get($baseUrl . '/view', [
            'filename' => $output['filename'],
            'subfolder' => $output['subfolder'] ?? '',
            'type' => $output['type'] ?? 'output',
        ]);
        $this->assertSuccessful($response, 'view');

        $mimeType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));
        if (
            !in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)
            || $response->body() === ''
        ) {
            throw new RuntimeException('ComfyUI a retourné un format d’image non pris en charge.');
        }

        return $response;
    }

    private function assertSuccessful(Response $response, string $operation): void
    {
        if ($response->failed()) {
            $detail = $response->json('error.message') ?? $response->body();
            throw new RuntimeException("ComfyUI {$operation} HTTP {$response->status()} : {$detail}");
        }
    }
}
