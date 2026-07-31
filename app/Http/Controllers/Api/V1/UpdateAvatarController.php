<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateAvatarRequest;
use App\Jobs\DeleteStoredAvatar;
use App\Models\User;
use App\Services\Media\ImageStorageService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAvatarController extends Controller
{
    public function __invoke(
        UpdateAvatarRequest $request,
        ImageStorageService $imageStorage,
    ): JsonResponse {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $userId = (string) $request->user()->getKey();
        $avatarPrefix = "avatars/{$userId}";
        $storedImage = $imageStorage->store(
            $file,
            $avatarPrefix.'/'.now()->format('Y/m'),
        );

        try {
            $oldAvatarPath = DB::transaction(function () use (
                $userId,
                $storedImage,
                $imageStorage,
                $avatarPrefix,
            ): ?string {
                /** @var User $user */
                $user = User::query()->lockForUpdate()->findOrFail($userId);
                $oldAvatarPath = $imageStorage->managedPathFromUrl(
                    $user->avatar_url,
                    $avatarPrefix,
                );

                $user->update(['avatar_url' => $storedImage['url']]);

                return $oldAvatarPath;
            });
        } catch (Throwable $exception) {
            $imageStorage->delete($storedImage['path']);

            throw $exception;
        }

        if ($oldAvatarPath !== null && $oldAvatarPath !== $storedImage['path']) {
            try {
                DeleteStoredAvatar::dispatch($oldAvatarPath, $userId);
            } catch (Throwable $exception) {
                Log::warning('Failed to queue old avatar deletion.', [
                    'user_id' => $userId,
                    'path' => $oldAvatarPath,
                    'exception' => $exception,
                ]);
            }
        }

        return ApiResponse::success(
            ['avatar_url' => $storedImage['url']],
            request: $request,
        );
    }
}
