<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImageUploadService
{
    /**
     * Store an uploaded image (or remove the existing one when requested) and return the new path.
     */
    public function syncImage(
        Request $request,
        string $field,
        string $directory,
        ?string $currentPath,
        bool $remove = false,
        string $errorMessage = 'Failed to store the uploaded image.'
    ): ?string {
        $disk = config('filesystems.uploads_disk', 'public');

        if ($request->hasFile($field)) {
            $path = $request->file($field)->store($directory, $disk);
            if ($path === false) {
                throw new RuntimeException($errorMessage);
            }

            if ($currentPath) {
                Storage::disk($disk)->delete($currentPath);
            }

            return $path;
        }

        if ($remove && $currentPath) {
            Storage::disk($disk)->delete($currentPath);

            return null;
        }

        return $currentPath;
    }
}
