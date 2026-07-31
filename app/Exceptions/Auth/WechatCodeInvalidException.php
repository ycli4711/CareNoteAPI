<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class WechatCodeInvalidException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The WeChat login code is invalid or expired.');
    }
}
