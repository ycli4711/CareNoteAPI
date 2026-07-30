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
        ->assertJsonPath('data.api_version', 'v1')
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('meta.request_id', 'test-request-001');
});

test('current user endpoint requires an app token', function () {
    $response = $this->getJson('/api/v1/me');

    $response
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED')
        ->assertJsonStructure(['message', 'code', 'errors', 'meta' => ['request_id']]);
});

test('app user token can access current user endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.status', 'active');
});

test('token without required ability is forbidden', function () {
    $user = User::factory()->create();
    $token = $user->createToken('limited', ['profile:read'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.FORBIDDEN');
});

test('admin session cannot authenticate as an app user', function () {
    $this->actingAs(AdminUser::factory()->create(), 'admin');

    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});

test('unknown api routes use the stable error envelope', function () {
    $this->getJson('/api/v1/not-found')
        ->assertNotFound()
        ->assertJsonPath('code', 'COMMON.NOT_FOUND')
        ->assertJsonStructure(['message', 'code', 'errors', 'meta' => ['request_id']]);
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
            'message',
            'code',
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
        ->assertJsonStructure(['message', 'code', 'errors', 'meta' => ['request_id']]);
});
