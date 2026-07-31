<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImageUploadRequest;
use App\Services\Media\ImageStorageService;
use App\Support\Api\ApiResponse;
use App\Support\Api\ApiSuccessCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class ImageUploadController extends Controller
{
    public function __invoke(
        ImageUploadRequest $request,
        ImageStorageService $imageStorage,
    ): JsonResponse {
        $storedPaths = [];

        try {
            $images = collect($request->file('images'))
                ->map(function (UploadedFile $image) use ($imageStorage, &$storedPaths): array {
                    $storedImage = $imageStorage->store(
                        $image,
                        'images/'.now()->format('Y/m'),
                    );
                    $storedPaths[] = $storedImage['path'];

                    return [
                        'id' => $storedImage['id'],
                        'url' => $storedImage['url'],
                        'mime_type' => $storedImage['mime_type'],
                        'size' => $storedImage['size'],
                    ];
                })
                ->all();
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                $imageStorage->delete($storedPaths);
            }

            throw $exception;
        }

        return ApiResponse::success(
            ['images' => $images],
            code: ApiSuccessCode::Created,
            request: $request,
        );
    }
}
