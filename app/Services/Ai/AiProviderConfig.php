<?php

namespace App\Services\Ai;

class AiProviderConfig
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $aiSceneModelId,
        public readonly string $provider,
        public readonly string $model,
        public readonly string $apiKey,
        public readonly string $baseUrl,
        public readonly string $endpoint,
        public readonly int $timeout,
        public readonly array $options,
        public readonly string $channelCode,
    ) {}
}
