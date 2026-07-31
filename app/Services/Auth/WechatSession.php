<?php

namespace App\Services\Auth;

readonly class WechatSession
{
    public function __construct(
        public string $openid,
        public ?string $unionId,
    ) {}
}
