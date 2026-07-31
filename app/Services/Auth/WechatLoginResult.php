<?php

namespace App\Services\Auth;

use App\Models\User;

readonly class WechatLoginResult
{
    public function __construct(
        public User $user,
        public TokenPair $tokens,
        public bool $isNew,
    ) {}
}
