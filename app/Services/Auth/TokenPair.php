<?php

namespace App\Services\Auth;

use Carbon\CarbonImmutable;

readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public CarbonImmutable $accessExpiresAt,
        public CarbonImmutable $refreshExpiresAt,
        public int $accessExpiresIn,
        public int $refreshExpiresIn,
    ) {}
}
