<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary.
     */
    public function upload(UploadedFile $file, ?string $folder = null): string
    {
        $cloudName = config('services.cloudinary.cloud_name', 'dh5wd8etl');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $targetFolder = $folder ?: config('services.cloudinary.folder', 'odometer');

        // If credentials are configured, upload directly to Cloudinary API
        if ($cloudName && $apiKey && $apiSecret) {
            $timestamp = time();
            $paramsToSign = "folder={$targetFolder}&timestamp={$timestamp}";
            $signature = sha1($paramsToSign.$apiSecret);

            $response = Http::timeout(25)
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

            Log::error('Cloudinary direct upload failed: '.$response->body());
            throw new \RuntimeException('Cloudinary image upload failed: '.$response->body());
        }

        // Standard storage when Cloudinary credentials are not provided
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

    /**
     * Returns an error placeholder SVG / data URI when an image fails to load.
     */
    public static function errorPlaceholder(): string
    {
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='100' viewBox='0 0 160 100'%3E%3Crect width='160' height='100' fill='%23fff1f2' stroke='%23fecdd3' rx='8'/%3E%3Ctext x='50%25' y='45%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='11' font-weight='bold' fill='%23e11d48'%3E%E2%9A%A0%EF%B8%8F Image Error%3C/text%3E%3Ctext x='50%25' y='65%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='9' fill='%239f1239'%3EFailed to load%3C/text%3E%3C/svg%3E";
    }
}
