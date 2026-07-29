<?php

namespace App\Support\Api;

enum ApiErrorCode: string
{
    case Unauthenticated = 'AUTH.UNAUTHENTICATED';
    case Forbidden = 'AUTH.FORBIDDEN';
    case ValidationFailed = 'COMMON.VALIDATION_FAILED';
    case NotFound = 'COMMON.NOT_FOUND';
    case RateLimited = 'COMMON.RATE_LIMITED';
    case HttpError = 'COMMON.HTTP_ERROR';
    case InternalError = 'COMMON.INTERNAL_ERROR';
}
