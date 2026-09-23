<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    /**
     * Allowed MIME types for upload validation.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/quicktime', 'video/x-msvideo',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip', 'application/x-zip-compressed',
    ];

    public function upload(UploadedFile $file, Agency $agency, int $userId, array $data = []): MediaAsset
    {
        // Validate MIME type using finfo on the actual file content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMimeType = $finfo->file($file->getRealPath());

        if (!in_array($realMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException("File type '{$realMimeType}' is not allowed. Allowed types: images, videos, PDF, Office documents, and ZIP archives.");
        }

        // Also verify the reported MIME matches the detected MIME
        $reportedMime = $file->getMimeType();
        if ($reportedMime !== $realMimeType) {
            throw new \InvalidArgumentException("MIME type mismatch: reported '{$reportedMime}' but detected '{$realMimeType}'. Possible spoofed file.");
        }

        $folder = $data['folder'] ?? 'uncategorized';
        $directory = "media/{$agency->id}/{$folder}";

        // Store file
        $path = $file->store($directory, 'public');

        // Detect file type
        $fileType = $this->detectFileType($realMimeType);

        // Get image dimensions
        $width = null;
        $height = null;
        if ($fileType === 'image') {
            $dimensions = getimagesize($file->getRealPath());
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        return MediaAsset::create([
            'agency_id' => $agency->id,
            'user_id' => $userId,
            'name' => $data['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
            'file_type' => $fileType,
            'mime_type' => $realMimeType,
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $data['alt_text'] ?? null,
            'folder' => $folder,
            'tags' => $data['tags'] ?? [],
            'is_public' => $data['is_public'] ?? false,
        ]);
    }

    public function delete(MediaAsset $asset): void
    {
        Storage::disk('public')->delete($asset->file_path);
        $asset->delete();
    }

    public function duplicate(MediaAsset $asset): MediaAsset
    {
        $newPath = $this->duplicateFile($asset->file_path);
        $newAsset = $asset->replicate();
        $newAsset->file_path = $newPath;
        $newAsset->name = $asset->name.' (Copy)';
        $newAsset->usage_count = 0;
        $newAsset->save();

        return $newAsset;
    }

    protected function duplicateFile(string $originalPath): string
    {
        $disk = Storage::disk('public');
        $pathInfo = pathinfo($originalPath);
        $newPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'-copy-'.Str::random(4).'.'.($pathInfo['extension'] ?? '');
        $disk->copy($originalPath, $newPath);

        return $newPath;
    }

    protected function detectFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }
}
