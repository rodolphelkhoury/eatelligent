<?php

namespace App\Actions;

use App\Models\Image;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UploadImage
{
    protected string $privateKey;

    protected string $urlEndpoint;

    public function __construct()
    {
        $this->privateKey = config('services.imagekit.private_key');
        $this->urlEndpoint = config('services.imagekit.url_endpoint');
    }

    /**
     * Upload a file to ImageKit and persist the result in the images table.
     */
    public function execute(
        UploadedFile $file,
        string $folder = 'images'
    ): Image {
        // Build a clean unique filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $fileName = str($originalName)->slug()->toString().'_'.uniqid().'.'.$extension;

        $stream = null;

        try {
            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new RuntimeException('Failed to open uploaded file for reading.');
            }

            /** @var HttpResponse $response */
            $response = Http::withBasicAuth($this->privateKey, '')
                ->attach('file', $stream, $fileName)
                ->post('https://upload.imagekit.io/api/v1/files/upload', [
                    'fileName' => $fileName,
                    'folder' => $folder,
                ]);
            if ($response->failed()) {
                throw new RuntimeException('ImageKit upload failed: '.$response->body());
            }

            $result = $response->json();

            if (! is_array($result) || empty($result['filePath'] ?? null)) {
                throw new RuntimeException('Unexpected ImageKit response: '.json_encode($result));
            }

            return Image::create([
                'disk' => 'imagekit',
                'name' => $result['name'] ?? $fileName,
                'filepath' => $result['filePath'],
                'mimetype' => $file->getMimeType(),
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'filesize' => $result['size'] ?? null,
            ]);
        } catch (\Exception $e) {
            throw new RuntimeException('Image upload failed: '.$e->getMessage(), 0, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
