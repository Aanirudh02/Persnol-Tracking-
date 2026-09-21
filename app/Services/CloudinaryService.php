<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary with automatic fallback to public disk storage.
     */
    public function upload(UploadedFile $file, ?string $folder = null): string
    {
        $cloudName = config('services.cloudinary.cloud_name', 'dh5wd8etl');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $targetFolder = $folder ?: config('services.cloudinary.folder', 'odometer');

        // If credentials are configured, try uploading directly to Cloudinary API
        if ($cloudName && $apiKey && $apiSecret) {
            try {
                $timestamp = time();
                // Cloudinary signature requires parameters to be sorted alphabetically
                $paramsToSign = "folder={$targetFolder}&timestamp={$timestamp}";
                $signature = sha1($paramsToSign.$apiSecret);

                $response = Http::timeout(20)
                    ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                    ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                        'api_key' => $apiKey,
                        'timestamp' => $timestamp,
                        'folder' => $targetFolder,
                        'signature' => $signature,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data['secure_url'])) {
                        return $data['secure_url'];
                    }
                }

                Log::warning('Cloudinary direct upload response unsuccessful: '.$response->body());
            } catch (\Throwable $e) {
                Log::warning('Cloudinary upload exception, falling back to local public storage: '.$e->getMessage());
            }
        }

        // Fallback to local storage
        return $file->store($targetFolder, 'public');
    }

    /**
     * Generate the accessible URL for an image path or external URL.
     */
    public static function url(?string $pathOrUrl): ?string
    {
        if (empty($pathOrUrl)) {
            return null;
        }

        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        return asset('storage/'.ltrim($pathOrUrl, '/'));
    }
}
