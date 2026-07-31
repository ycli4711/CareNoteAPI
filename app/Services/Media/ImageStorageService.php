<?php

namespace App\Services\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageStorageService
{
    /**
     * @return array{id: string, path: string, url: string, mime_type: ?string, size: int|false}
     */
    public function store(UploadedFile $image, string $directory): array
    {
        $id = (string) Str::uuid();
        $filename = $id.'.'.$image->extension();
        $path = $this->disk()->putFileAs(trim($directory, '/'), $image, $filename);

        if ($path === false) {
            throw new RuntimeException('Image storage failed.');
        }

        return [
            'id' => $id,
            'path' => $path,
            'url' => $this->disk()->url($path),
            'mime_type' => $image->getMimeType(),
            'size' => $image->getSize(),
        ];
    }

    /**
     * @param  string|array<int, string>  $paths
     */
    public function delete(string|array $paths): bool
    {
        return $this->disk()->delete($paths);
    }

    public function managedPathFromUrl(?string $url, string $requiredPrefix): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $baseUrl = rtrim($this->disk()->url(''), '/').'/';

        if (! str_starts_with($url, $baseUrl)) {
            return null;
        }

        $path = ltrim(substr($url, strlen($baseUrl)), '/');
        $requiredPrefix = trim($requiredPrefix, '/').'/';

        return str_starts_with($path, $requiredPrefix) ? $path : null;
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.image_upload_disk', 'r2'));
    }
}
