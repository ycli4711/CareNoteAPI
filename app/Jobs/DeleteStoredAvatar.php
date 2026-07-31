<?php

namespace App\Jobs;

use App\Services\Media\ImageStorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteStoredAvatar implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $path,
        public string $userId,
    ) {}

    public function handle(ImageStorageService $imageStorage): void
    {
        if (str_starts_with($this->path, "avatars/{$this->userId}/")) {
            $imageStorage->delete($this->path);
        }
    }
}
