<?php

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('services.wechat_mini_program', [
        'app_id' => 'test-app-id',
        'app_secret' => 'test-app-secret',
        'access_token_ttl_seconds' => 86400,
        'refresh_token_ttl_seconds' => 2592000,
    ]);
});

function fakeWechatLogin(
    string $openid = 'openid-001',
    ?string $unionId = 'unionid-001',
    string $sessionKey = 'session-key-sensitive',
): void {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response(array_filter([
            'openid' => $openid,
            'unionid' => $unionId,
            'session_key' => $sessionKey,
        ], fn (?string $value): bool => $value !== null)),
    ]);
}

test('new user can log in with a WeChat code', function () {
    fakeWechatLogin();

    $response = $this->postJson('/api/v1/auth/wechat/login', [
        'code' => 'temporary-code',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 'COMMON.OK')
        ->assertJsonPath('message', '登录成功。')
        ->assertJsonPath('data.expires_in', 86400)
        ->assertJsonPath('data.refresh_expires_in', 2592000)
        ->assertJsonPath('data.is_new_user', true)
        ->assertJsonPath('data.user.nickname', 'CareNote 用户')
        ->assertJsonPath('data.user.avatar_url', null)
        ->assertJsonPath('data.user.tracking_enabled', false)
        ->assertJsonPath('data.user.privacy_v1_1_seen', null)
        ->assertJsonPath('data.user.onboarding', [
            'current_step' => 0,
            'started_at' => null,
            'completed_at' => null,
            'skipped' => false,
            'selected_member_id' => null,
            'selected_medicine_id' => null,
        ])
        ->assertJsonMissingPath('data.token_type')
        ->assertJsonMissingPath('data.user.phone')
        ->assertJsonMissingPath('data.user.onboarding.medicine_draft')
        ->assertJsonMissing(['openid' => 'openid-001'])
        ->assertJsonMissing(['unionid' => 'unionid-001'])
        ->assertJsonMissing(['session_key' => 'session-key-sensitive']);

    $user = User::query()->sole();
    $identity = UserIdentity::query()->sole();

    expect($identity->user_id)->toBe($user->id)
        ->and($identity->provider)->toBe('wechat_mini_program')
        ->and($identity->provider_subject)->toBe('openid-001')
        ->and($identity->union_id)->toBe('unionid-001');

    $plainTextToken = $response->json('data.access_token');
    $plainTextRefreshToken = $response->json('data.refresh_token');
    $token = PersonalAccessToken::findToken($plainTextToken);
    $refreshToken = PersonalAccessToken::findToken($plainTextRefreshToken);

    expect($token)->not->toBeNull()
        ->and($token->can('app:access'))->toBeTrue()
        ->and($token->token_kind)->toBe('access')
        ->and($token->expires_at->isBetween(now()->addHours(23), now()->addHours(25)))->toBeTrue()
        ->and($refreshToken)->not->toBeNull()
        ->and($refreshToken->can('auth:refresh'))->toBeTrue()
        ->and($refreshToken->token_kind)->toBe('refresh')
        ->and($refreshToken->token_family_id)->toBe($token->token_family_id)
        ->and($refreshToken->expires_at->isBetween(now()->addDays(29), now()->addDays(31)))->toBeTrue();

    $this->withToken($plainTextToken)
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.nickname', 'CareNote 用户');
});

test('existing identity reuses its user without changing its profile', function () {
    $user = User::factory()->create([
        'display_name' => '原昵称',
        'avatar_url' => 'https://example.com/old.jpg',
    ]);
    $user->identities()->create([
        'provider' => 'wechat_mini_program',
        'provider_subject' => 'openid-001',
    ]);
    fakeWechatLogin();

    $response = $this->postJson('/api/v1/auth/wechat/login', [
        'code' => 'temporary-code',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.is_new_user', false)
        ->assertJsonPath('data.user.nickname', '原昵称')
        ->assertJsonPath('data.user.avatar_url', 'https://example.com/old.jpg');

    expect(User::query()->count())->toBe(1)
        ->and(UserIdentity::query()->count())->toBe(1)
        ->and($user->refresh()->last_active_at)->not->toBeNull();
});

test('new user gets a stable default name', function () {
    fakeWechatLogin(unionId: null);

    $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code'])
        ->assertOk()
        ->assertJsonPath('data.user.nickname', 'CareNote 用户');
});

test('invalid WeChat code returns a stable API error', function () {
    Http::fake([
        'api.weixin.qq.com/*' => Http::response([
            'errcode' => 40029,
            'errmsg' => 'invalid code',
        ]),
    ]);

    $this->postJson('/api/v1/auth/wechat/login', ['code' => 'invalid-code'])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.WECHAT_CODE_INVALID')
        ->assertJsonStructure(['message', 'code', 'errors', 'meta' => ['request_id']]);
});

test('disabled account cannot log in or access protected endpoints', function () {
    $user = User::factory()->create(['status' => 'disabled']);
    $user->identities()->create([
        'provider' => 'wechat_mini_program',
        'provider_subject' => 'openid-001',
    ]);
    fakeWechatLogin();

    $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code'])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.ACCOUNT_DISABLED');

    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.ACCOUNT_DISABLED');
});

test('logout revokes the current token session only', function () {
    $user = User::factory()->create();
    $user->identities()->create([
        'provider' => 'wechat_mini_program',
        'provider_subject' => 'openid-001',
    ]);
    fakeWechatLogin();
    $login = $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code']);
    $currentToken = $login->json('data.access_token');
    $currentRefreshToken = $login->json('data.refresh_token');
    $otherToken = $user->createToken('other', ['app:access'])->plainTextToken;
    $currentTokenId = PersonalAccessToken::findToken($currentToken)->getKey();

    $this->withToken($currentToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.revoked', true);

    expect(PersonalAccessToken::find($currentTokenId)->revoked_at)->not->toBeNull();
    $this->app['auth']->forgetGuards();

    $this->withToken($currentToken)
        ->getJson('/api/v1/users/me')
        ->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    $this->withToken($otherToken)
        ->getJson('/api/v1/users/me')
        ->assertOk();

    $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $currentRefreshToken,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.SESSION_REVOKED');
});

test('WeChat login request validates its contract', function () {
    $this->postJson('/api/v1/auth/wechat/login', [
        'code' => ' ',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonStructure([
            'errors' => ['code'],
        ]);
});

test('WeChat login has an independent rate limit', function () {
    fakeWechatLogin();

    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/v1/auth/wechat/login', ['code' => "code-{$attempt}"])
            ->assertOk();
    }

    $this->postJson('/api/v1/auth/wechat/login', ['code' => 'code-11'])
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'COMMON.RATE_LIMITED');
});

test('refresh token atomically rotates both tokens', function () {
    fakeWechatLogin();
    $login = $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code']);
    $oldAccessToken = $login->json('data.access_token');
    $oldRefreshToken = $login->json('data.refresh_token');

    $response = $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $oldRefreshToken,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Token 刷新成功。')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.expires_in', 86400)
        ->assertJsonPath('data.refresh_expires_in', 2592000);

    $newAccessToken = $response->json('data.access_token');
    $newRefreshToken = $response->json('data.refresh_token');

    expect($newAccessToken)->not->toBe($oldAccessToken)
        ->and($newRefreshToken)->not->toBe($oldRefreshToken)
        ->and(PersonalAccessToken::findToken($oldRefreshToken)->revoked_at)->not->toBeNull();

    $this->app['auth']->forgetGuards();
    $this->withToken($oldAccessToken)->getJson('/api/v1/users/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->withToken($newAccessToken)->getJson('/api/v1/users/me')->assertOk();
});

test('reusing a rotated refresh token revokes its replacement session', function () {
    fakeWechatLogin();
    $login = $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code']);
    $oldRefreshToken = $login->json('data.refresh_token');
    $refreshed = $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $oldRefreshToken,
    ]);
    $newAccessToken = $refreshed->json('data.access_token');

    $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $oldRefreshToken,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.SESSION_REVOKED');

    $this->app['auth']->forgetGuards();
    $this->withToken($newAccessToken)->getJson('/api/v1/users/me')->assertUnauthorized();
});

test('refresh endpoint distinguishes invalid and expired tokens', function () {
    $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => 'invalid-token',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.REFRESH_TOKEN_INVALID');

    $user = User::factory()->create();
    $expired = $user->createToken(
        'expired-refresh',
        ['auth:refresh'],
        now()->subSecond(),
    );
    $expired->accessToken->forceFill([
        'token_kind' => 'refresh',
        'token_family_id' => (string) Str::ulid(),
    ])->save();

    $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $expired->plainTextToken,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.REFRESH_TOKEN_EXPIRED');
});

test('refresh token cannot authenticate protected endpoints', function () {
    fakeWechatLogin();
    $login = $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code']);

    $this->withToken($login->json('data.refresh_token'))
        ->getJson('/api/v1/users/me')
        ->assertUnauthorized();
});

test('disabled account cannot refresh its token pair', function () {
    fakeWechatLogin();
    $login = $this->postJson('/api/v1/auth/wechat/login', ['code' => 'temporary-code']);
    User::query()->sole()->update(['status' => 'disabled']);

    $this->postJson('/api/v1/auth/token/refresh', [
        'refresh_token' => $login->json('data.refresh_token'),
    ])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.ACCOUNT_DISABLED');
});
