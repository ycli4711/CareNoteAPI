<?php

namespace App\Support\Api;

enum ApiErrorCode: string
{
    case Unauthenticated = 'AUTH.UNAUTHENTICATED';
    case Forbidden = 'AUTH.FORBIDDEN';
    case WechatCodeInvalid = 'AUTH.WECHAT_CODE_INVALID';
    case RefreshTokenInvalid = 'AUTH.REFRESH_TOKEN_INVALID';
    case RefreshTokenExpired = 'AUTH.REFRESH_TOKEN_EXPIRED';
    case SessionRevoked = 'AUTH.SESSION_REVOKED';
    case AccountDisabled = 'AUTH.ACCOUNT_DISABLED';
    case WechatUpstreamError = 'AUTH.WECHAT_UPSTREAM_ERROR';
    case WechatUnavailable = 'AUTH.WECHAT_UNAVAILABLE';
    case BadRequest = 'COMMON.BAD_REQUEST';
    case ValidationFailed = 'COMMON.VALIDATION_FAILED';
    case NotFound = 'COMMON.NOT_FOUND';
    case MethodNotAllowed = 'COMMON.METHOD_NOT_ALLOWED';
    case Conflict = 'COMMON.CONFLICT';
    case PayloadTooLarge = 'COMMON.PAYLOAD_TOO_LARGE';
    case UnsupportedMediaType = 'COMMON.UNSUPPORTED_MEDIA_TYPE';
    case RateLimited = 'COMMON.RATE_LIMITED';
    case HttpError = 'COMMON.HTTP_ERROR';
    case InternalError = 'COMMON.INTERNAL_ERROR';
    case BadGateway = 'UPSTREAM.BAD_GATEWAY';
    case ServiceUnavailable = 'UPSTREAM.SERVICE_UNAVAILABLE';
    case GatewayTimeout = 'UPSTREAM.GATEWAY_TIMEOUT';

    public function status(): int
    {
        return match ($this) {
            self::BadRequest,
            self::HttpError => 400,
            self::Unauthenticated,
            self::WechatCodeInvalid,
            self::RefreshTokenInvalid,
            self::RefreshTokenExpired,
            self::SessionRevoked => 401,
            self::Forbidden,
            self::AccountDisabled => 403,
            self::NotFound => 404,
            self::MethodNotAllowed => 405,
            self::Conflict => 409,
            self::PayloadTooLarge => 413,
            self::UnsupportedMediaType => 415,
            self::ValidationFailed => 422,
            self::RateLimited => 429,
            self::InternalError => 500,
            self::BadGateway,
            self::WechatUpstreamError => 502,
            self::ServiceUnavailable,
            self::WechatUnavailable => 503,
            self::GatewayTimeout => 504,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::Unauthenticated => '请先登录。',
            self::Forbidden => '没有执行该操作的权限。',
            self::WechatCodeInvalid => '微信登录凭证无效或已过期，请重试。',
            self::RefreshTokenInvalid => '刷新令牌无效，请重新登录。',
            self::RefreshTokenExpired => '刷新令牌已过期，请重新登录。',
            self::SessionRevoked => '当前登录会话已撤销，请重新登录。',
            self::AccountDisabled => '当前账户不可用。',
            self::WechatUpstreamError => '微信登录服务返回异常，请稍后重试。',
            self::WechatUnavailable => '微信登录服务暂时不可用，请稍后重试。',
            self::BadRequest => '请求格式或参数有误。',
            self::ValidationFailed => '请求参数校验失败。',
            self::NotFound => '请求的资源不存在。',
            self::MethodNotAllowed => '请求方法不被允许。',
            self::Conflict => '请求与资源当前状态冲突。',
            self::PayloadTooLarge => '请求内容过大。',
            self::UnsupportedMediaType => '不支持当前请求的媒体类型。',
            self::RateLimited => '请求过于频繁，请稍后重试。',
            self::HttpError => '请求无法处理。',
            self::InternalError => '服务器暂时无法处理请求。',
            self::BadGateway => '上游服务返回异常。',
            self::ServiceUnavailable => '服务暂时不可用，请稍后重试。',
            self::GatewayTimeout => '上游服务响应超时，请稍后重试。',
        };
    }

    public static function fromHttpStatus(int $status): self
    {
        return match ($status) {
            400 => self::BadRequest,
            401 => self::Unauthenticated,
            403 => self::Forbidden,
            404 => self::NotFound,
            405 => self::MethodNotAllowed,
            409 => self::Conflict,
            413 => self::PayloadTooLarge,
            415 => self::UnsupportedMediaType,
            422 => self::ValidationFailed,
            429 => self::RateLimited,
            502 => self::BadGateway,
            503 => self::ServiceUnavailable,
            504 => self::GatewayTimeout,
            default => $status >= 500
                ? self::InternalError
                : self::HttpError,
        };
    }
}
