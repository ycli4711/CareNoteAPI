<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class WechatUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The WeChat login service is temporarily unavailable.');
    }
}
