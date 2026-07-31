<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('ping uses the stable api envelope and request id', function () {
    $response = $this->withHeader('X-Request-ID', 'test-request-001')
        ->getJson('/api/v1/ping');

    $response
        ->assertOk()
        ->assertHeader('X-Request-ID', 'test-request-001')
        ->assertJsonPath('success', true)
        ->assertJsonPath('code', 'COMMON.OK')
        ->assertJsonPath('message', '操作成功。')
        ->assertJsonPath('data.api_version', 'v1')
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('meta.request_id', 'test-request-001')
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
            'meta' => ['request_id'],
        ]);
});

test('current user endpoint requires an app token', function () {
    $response = $this->getJson('/api/v1/users/me');

    $response
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED')
        ->assertJsonPath('data', null)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
            'meta' => ['request_id'],
        ]);
});

test('app user token can access current user endpoint', function () {
    $user = User::factory()->create([
        'display_name' => '微信用户',
        'gender' => null,
        'tracking_enabled' => true,
        'privacy_v1_1_seen' => false,
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/users/me');

    $response
        ->assertOk()
        ->assertJsonPath('data', [
            'id' => $user->id,
            'nickname' => '微信用户',
            'avatar_url' => null,
            'gender' => 'unset',
            'tracking_enabled' => true,
            'privacy_v1_1_seen' => false,
            'onboarding' => [
                'current_step' => 0,
                'started_at' => null,
                'completed_at' => null,
                'skipped' => false,
                'selected_member_id' => null,
                'selected_medicine_id' => null,
            ],
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]);
});

test('token without required ability is forbidden', function () {
    $user = User::factory()->create();
    $token = $user->createToken('limited', ['profile:read'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.FORBIDDEN');
});

test('admin session cannot authenticate as an app user', function () {
    $this->actingAs(AdminUser::factory()->create(), 'admin');

    $this->getJson('/api/v1/users/me')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});

test('legacy current user endpoint is not available', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertNotFound();
});

test('app user can update profile and preferences', function () {
    $user = User::factory()->create([
        'display_name' => '原昵称',
        'gender' => null,
        'tracking_enabled' => true,
        'privacy_v1_1_seen' => false,
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/users/me', [
            'nickname' => '  新昵称  ',
            'gender' => 'female',
            'tracking_enabled' => false,
            'privacy_v1_1_seen' => true,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.nickname', '新昵称')
        ->assertJsonPath('data.gender', 'female')
        ->assertJsonPath('data.tracking_enabled', false)
        ->assertJsonPath('data.privacy_v1_1_seen', true)
        ->assertJsonStructure([
            'data' => [
                'id',
                'nickname',
                'avatar_url',
                'gender',
                'tracking_enabled',
                'privacy_v1_1_seen',
                'onboarding',
                'created_at',
                'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'display_name' => '新昵称',
        'gender' => 'female',
        'tracking_enabled' => false,
        'privacy_v1_1_seen' => true,
    ]);
});

test('updating current user only changes fields present in the request', function () {
    $user = User::factory()->create([
        'display_name' => '原昵称',
        'gender' => 'male',
        'tracking_enabled' => true,
        'privacy_v1_1_seen' => false,
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me', [
            'privacy_v1_1_seen' => true,
        ])
        ->assertOk();

    $user->refresh();

    expect($user->display_name)->toBe('原昵称')
        ->and($user->gender)->toBe('male')
        ->and($user->tracking_enabled)->toBeTrue()
        ->and($user->privacy_v1_1_seen)->toBeTrue();
});

test('updating current user requires at least one supported field', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me')
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonStructure(['errors' => ['profile']]);
});

test('current user profile fields use strict validation', function (array $payload, string $field) {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonStructure(['errors' => [$field]]);
})->with([
    'blank nickname' => [['nickname' => '   '], 'nickname'],
    'nickname longer than 20 characters' => [['nickname' => str_repeat('名', 21)], 'nickname'],
    'unsupported gender' => [['gender' => 'unknown'], 'gender'],
    'numeric tracking switch' => [['tracking_enabled' => 1], 'tracking_enabled'],
    'string privacy switch' => [['privacy_v1_1_seen' => 'true'], 'privacy_v1_1_seen'],
]);

test('updating current user requires an app token', function () {
    $this->postJson('/api/v1/users/me', [
        'nickname' => '新昵称',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});

test('unknown api routes use the stable error envelope', function () {
    $this->getJson('/api/v1/not-found')
        ->assertNotFound()
        ->assertJsonPath('code', 'COMMON.NOT_FOUND')
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
            'meta' => ['request_id'],
        ]);
});

test('validation errors use the stable api error envelope', function () {
    Route::middleware('api')->post('/api/v1/test-validation', function (Request $request) {
        $request->validate([
            'name' => ['required', 'string'],
        ]);

        return response()->noContent();
    });

    $this->withHeader('X-Request-ID', 'test-validation-request')
        ->postJson('/api/v1/test-validation')
        ->assertUnprocessable()
        ->assertHeader('X-Request-ID', 'test-validation-request')
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonPath('meta.request_id', 'test-validation-request')
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors' => ['name'],
            'meta' => ['request_id'],
        ]);
});

test('rate limits use the stable api error envelope', function () {
    Route::middleware(['api', 'throttle:1,1'])
        ->get('/api/v1/test-rate-limit', fn () => response()->json(['status' => 'ok']));

    $this->getJson('/api/v1/test-rate-limit')->assertOk();

    $this->withHeader('X-Request-ID', 'test-rate-limit-request')
        ->getJson('/api/v1/test-rate-limit')
        ->assertTooManyRequests()
        ->assertHeader('X-Request-ID', 'test-rate-limit-request')
        ->assertJsonPath('code', 'COMMON.RATE_LIMITED')
        ->assertJsonPath('meta.request_id', 'test-rate-limit-request')
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data',
            'errors',
            'meta' => ['request_id'],
        ]);
});

test('standard http exceptions use documented business codes', function () {
    $cases = [
        400 => 'COMMON.BAD_REQUEST',
        405 => 'COMMON.METHOD_NOT_ALLOWED',
        409 => 'COMMON.CONFLICT',
        413 => 'COMMON.PAYLOAD_TOO_LARGE',
        415 => 'COMMON.UNSUPPORTED_MEDIA_TYPE',
        502 => 'UPSTREAM.BAD_GATEWAY',
        503 => 'UPSTREAM.SERVICE_UNAVAILABLE',
        504 => 'UPSTREAM.GATEWAY_TIMEOUT',
    ];

    foreach ($cases as $status => $code) {
        Route::middleware('api')->get(
            "/api/v1/test-http-{$status}",
            fn () => abort($status),
        );

        $this->getJson("/api/v1/test-http-{$status}")
            ->assertStatus($status)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', $code)
            ->assertJsonPath('data', null);
    }
});
