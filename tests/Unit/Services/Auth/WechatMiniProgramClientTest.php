<?php

use App\Exceptions\Auth\WechatCodeInvalidException;
use App\Exceptions\Auth\WechatUnavailableException;
use App\Exceptions\Auth\WechatUpstreamException;
use App\Services\Auth\WechatMiniProgramClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('services.wechat_mini_program', [
        'app_id' => 'test-app-id',
        'app_secret' => 'test-app-secret',
        'access_token_ttl_seconds' => 86400,
        'refresh_token_ttl_seconds' => 2592000,
    ]);
});

test('exchanges a code for a normalized WeChat session', function () {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response([
            'openid' => 'openid-001',
            'unionid' => 'unionid-001',
            'session_key' => 'sensitive-session-key',
        ]),
    ]);

    $session = app(WechatMiniProgramClient::class)->exchangeCode('temporary-code');

    expect($session->openid)->toBe('openid-001')
        ->and($session->unionId)->toBe('unionid-001');

    Http::assertSent(fn ($request): bool => $request['appid'] === 'test-app-id'
        && $request['secret'] === 'test-app-secret'
        && $request['js_code'] === 'temporary-code'
        && $request['grant_type'] === 'authorization_code');
});

test('maps invalid and already used codes to the domain exception', function (int $errorCode) {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response([
            'errcode' => $errorCode,
            'errmsg' => 'invalid',
        ]),
    ]);

    expect(fn () => app(WechatMiniProgramClient::class)->exchangeCode('invalid-code'))
        ->toThrow(WechatCodeInvalidException::class);
})->with([40029, 40163]);

test('retries a server failure once and then succeeds', function () {
    Http::fakeSequence()
        ->pushStatus(503)
        ->push([
            'openid' => 'openid-after-retry',
            'session_key' => 'sensitive-session-key',
        ]);

    $session = app(WechatMiniProgramClient::class)->exchangeCode('temporary-code');

    expect($session->openid)->toBe('openid-after-retry');
    Http::assertSentCount(2);
});

test('maps connection failures to unavailable without leaking credentials', function () {
    Http::fake([
        'api.weixin.qq.com/*' => Http::failedConnection('connection failed'),
    ]);

    try {
        app(WechatMiniProgramClient::class)->exchangeCode('temporary-code');
        $this->fail('Expected a WechatUnavailableException.');
    } catch (WechatUnavailableException $exception) {
        expect($exception->getMessage())
            ->not->toContain('test-app-secret')
            ->not->toContain('temporary-code');
    }

    Http::assertSentCount(2);
});

test('maps unrecognized WeChat errors to an upstream exception', function () {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response([
            'errcode' => 40226,
            'errmsg' => 'high risk user',
        ]),
    ]);

    expect(fn () => app(WechatMiniProgramClient::class)->exchangeCode('temporary-code'))
        ->toThrow(WechatUpstreamException::class);
});

test('rejects malformed success responses', function () {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response([
            'session_key' => 'missing-openid',
        ]),
    ]);

    expect(fn () => app(WechatMiniProgramClient::class)->exchangeCode('temporary-code'))
        ->toThrow(WechatUpstreamException::class);
});
