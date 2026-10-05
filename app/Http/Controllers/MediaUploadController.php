<?php

namespace App\Http\Controllers;

use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaUploadController extends Controller
{
    /**
     * Global unified media upload endpoint.
     * Uploads to Cloudinary (or fallback public disk) and returns the standard accessible URL.
     */
    public function upload(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $request->validate([
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif,svg|max:12288',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif,svg,pdf|max:12288',
            'folder' => 'nullable|string|max:100',
        ]);

        $uploadedFile = $request->file('image') ?? $request->file('file');

        if (! $uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'No media file provided for upload.',
            ], 422);
        }

        try {
            $folder = $request->input('folder', 'uploads');
            $url = $cloudinary->upload($uploadedFile, $folder);
            $fullUrl = CloudinaryService::url($url);

            return response()->json([
                'success' => true,
                'path' => $url,
                'url' => $fullUrl,
                'name' => $uploadedFile->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
            ]);
        } catch (\Throwable $e) {
            Log::error('MediaUploadController failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
