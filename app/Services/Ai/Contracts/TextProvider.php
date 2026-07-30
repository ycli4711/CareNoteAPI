<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiProviderConfig;
use App\Services\Ai\AiTextResult;

interface TextProvider
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function complete(array $messages, AiProviderConfig $config): AiTextResult;
}
