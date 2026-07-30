<?php

namespace App\Services\Ai;

class AiTextResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public readonly string $content,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly array $raw = [],
    ) {}
}
