<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class RefreshTokenInvalidException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The refresh token is invalid.');
    }
}
