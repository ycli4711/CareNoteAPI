<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\WechatCodeInvalidException;
use App\Exceptions\Auth\WechatUnavailableException;
use App\Exceptions\Auth\WechatUpstreamException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WechatMiniProgramClient
{
    private const ENDPOINT = 'https://api.weixin.qq.com/sns/jscode2session';

    public function exchangeCode(string $code): WechatSession
    {
        $appId = (string) config('services.wechat_mini_program.app_id');
        $appSecret = (string) config('services.wechat_mini_program.app_secret');

        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('WeChat mini program credentials are not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->retry(
                    2,
                    100,
                    fn (Throwable $exception, PendingRequest $request): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false,
                )
                ->get(self::ENDPOINT, [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code',
                ]);
        } catch (ConnectionException) {
            throw new WechatUnavailableException;
        }

        if ($response->serverError()) {
            throw new WechatUnavailableException;
        }

        if (! $response->successful()) {
            throw new WechatUpstreamException;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new WechatUpstreamException;
        }

        $errorCode = (int) ($payload['errcode'] ?? 0);

        if (in_array($errorCode, [40029, 40163], true)) {
            throw new WechatCodeInvalidException;
        }

        if ($errorCode === -1) {
            throw new WechatUnavailableException;
        }

        if ($errorCode !== 0) {
            throw new WechatUpstreamException;
        }

        $openid = $payload['openid'] ?? null;

        if (! is_string($openid) || $openid === '') {
            throw new WechatUpstreamException;
        }

        $unionId = $payload['unionid'] ?? null;

        return new WechatSession(
            openid: $openid,
            unionId: is_string($unionId) && $unionId !== '' ? $unionId : null,
        );
    }
}
