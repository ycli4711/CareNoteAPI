<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class RefreshTokenExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The refresh token has expired.');
    }
}
