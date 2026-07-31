<?php

namespace App\Support\Api;

use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\RefreshTokenExpiredException;
use App\Exceptions\Auth\RefreshTokenInvalidException;
use App\Exceptions\Auth\SessionRevokedException;
use App\Exceptions\Auth\WechatCodeInvalidException;
use App\Exceptions\Auth\WechatUnavailableException;
use App\Exceptions\Auth\WechatUpstreamException;
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
            $exception instanceof WechatCodeInvalidException => ApiResponse::error(
                ApiErrorCode::WechatCodeInvalid,
                request: $request,
            ),
            $exception instanceof RefreshTokenInvalidException => ApiResponse::error(
                ApiErrorCode::RefreshTokenInvalid,
                request: $request,
            ),
            $exception instanceof RefreshTokenExpiredException => ApiResponse::error(
                ApiErrorCode::RefreshTokenExpired,
                request: $request,
            ),
            $exception instanceof SessionRevokedException => ApiResponse::error(
                ApiErrorCode::SessionRevoked,
                request: $request,
            ),
            $exception instanceof AccountDisabledException => ApiResponse::error(
                ApiErrorCode::AccountDisabled,
                request: $request,
            ),
            $exception instanceof WechatUpstreamException => ApiResponse::error(
                ApiErrorCode::WechatUpstreamError,
                request: $request,
            ),
            $exception instanceof WechatUnavailableException => ApiResponse::error(
                ApiErrorCode::WechatUnavailable,
                request: $request,
            ),
            $exception instanceof ValidationException => ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                $exception->errors(),
                request: $request,
            ),
            $exception instanceof AuthenticationException => ApiResponse::error(
                ApiErrorCode::Unauthenticated,
                request: $request,
            ),
            $exception instanceof AuthorizationException => ApiResponse::error(
                ApiErrorCode::Forbidden,
                request: $request,
            ),
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => ApiResponse::error(
                ApiErrorCode::NotFound,
                request: $request,
            ),
            $exception instanceof TooManyRequestsHttpException => ApiResponse::error(
                ApiErrorCode::RateLimited,
                request: $request,
            ),
            $exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403 => ApiResponse::error(
                ApiErrorCode::Forbidden,
                request: $request,
            ),
            $exception instanceof HttpExceptionInterface => ApiResponse::error(
                ApiErrorCode::fromHttpStatus($exception->getStatusCode()),
                status: $exception->getStatusCode(),
                request: $request,
            ),
            default => ApiResponse::error(
                ApiErrorCode::InternalError,
                request: $request,
            ),
        };
    }
}
