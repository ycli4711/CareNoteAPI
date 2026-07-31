<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class SessionRevokedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The token session has been revoked.');
    }
}
