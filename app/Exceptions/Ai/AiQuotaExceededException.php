<?php

namespace App\Exceptions\Ai;

use RuntimeException;

class AiQuotaExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('当前AI场景的可用次数已用完。');
    }
}
