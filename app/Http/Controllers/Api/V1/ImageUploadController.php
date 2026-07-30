<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImageUploadRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImageUploadController extends Controller
{
    public function __invoke(ImageUploadRequest $request): JsonResponse
    {
        $disk = Storage::disk(config('filesystems.image_upload_disk', 'r2'));
        $storedPaths = [];

        try {
            $images = collect($request->file('images'))
                ->map(function (UploadedFile $image) use ($disk, &$storedPaths): array {
                    $storedImage = $this->store($disk, $image);
                    $storedPaths[] = $storedImage['path'];

                    return [
                        'id' => $storedImage['id'],
                        'url' => $disk->url($storedImage['path']),
                        'mime_type' => $image->getMimeType(),
                        'size' => $image->getSize(),
                    ];
                })
                ->all();
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                $disk->delete($storedPaths);
            }

            throw $exception;
        }

        return ApiResponse::success(
            ['images' => $images],
            status: 201,
            request: $request,
        );
    }

    /**
     * @return array{id: string, path: string}
     */
    private function store(Filesystem $disk, UploadedFile $image): array
    {
        $directory = 'images/'.now()->format('Y/m');
        $id = (string) Str::uuid();
        $filename = $id.'.'.$image->extension();
        $path = $disk->putFileAs($directory, $image, $filename);

        if ($path === false) {
            throw new RuntimeException('Image storage failed.');
        }

        return [
            'id' => $id,
            'path' => $path,
        ];
    }
}
