<?php

namespace App\Support\Api;

enum ApiSuccessCode: string
{
    case Ok = 'COMMON.OK';
    case Created = 'COMMON.CREATED';

    public function status(): int
    {
        return match ($this) {
            self::Ok => 200,
            self::Created => 201,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::Ok => '操作成功。',
            self::Created => '创建成功。',
        };
    }
}
