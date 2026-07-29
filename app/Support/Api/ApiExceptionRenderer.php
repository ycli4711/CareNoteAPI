<?php

namespace App\Support\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionRenderer
{
    public function render(Throwable $exception, Request $request): JsonResponse
    {
        return match (true) {
            $exception instanceof ValidationException => ApiResponse::error(
                '请求参数校验失败。',
                ApiErrorCode::ValidationFailed,
                422,
                $exception->errors(),
                request: $request,
            ),
            $exception instanceof AuthenticationException => ApiResponse::error(
                '请先登录。',
                ApiErrorCode::Unauthenticated,
                401,
                request: $request,
            ),
            $exception instanceof AuthorizationException => ApiResponse::error(
                '没有执行该操作的权限。',
                ApiErrorCode::Forbidden,
                403,
                request: $request,
            ),
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => ApiResponse::error(
                '请求的资源不存在。',
                ApiErrorCode::NotFound,
                404,
                request: $request,
            ),
            $exception instanceof TooManyRequestsHttpException => ApiResponse::error(
                '请求过于频繁，请稍后重试。',
                ApiErrorCode::RateLimited,
                429,
                request: $request,
            ),
            $exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403 => ApiResponse::error(
                '没有执行该操作的权限。',
                ApiErrorCode::Forbidden,
                403,
                request: $request,
            ),
            $exception instanceof HttpExceptionInterface => ApiResponse::error(
                '请求无法处理。',
                ApiErrorCode::HttpError,
                $exception->getStatusCode(),
                request: $request,
            ),
            default => ApiResponse::error(
                '服务器暂时无法处理请求。',
                ApiErrorCode::InternalError,
                500,
                request: $request,
            ),
        };
    }
}
