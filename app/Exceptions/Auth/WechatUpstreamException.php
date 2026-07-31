<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class WechatUpstreamException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The WeChat login service returned an unexpected response.');
    }
}
