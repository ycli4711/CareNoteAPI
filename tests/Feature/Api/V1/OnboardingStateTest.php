<?php

use App\Models\User;
use Illuminate\Support\Carbon;

test('app user can update onboarding state by merging provided fields', function () {
    $this->travelTo(Carbon::parse('2026-07-31T10:00:00+08:00'));
    $user = User::factory()->create([
        'onboarding' => [
            'current_step' => 1,
            'started_at' => null,
            'completed_at' => null,
            'skipped' => false,
            'selected_member_id' => null,
            'selected_medicine_id' => 'medicine-id',
        ],
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding', [
            'current_step' => 2,
            'selected_member_id' => 'member-id',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data', [
            'current_step' => 2,
            'started_at' => now()->toISOString(),
            'completed_at' => null,
            'skipped' => false,
            'selected_member_id' => 'member-id',
            'selected_medicine_id' => 'medicine-id',
        ]);

    expect($user->refresh()->onboarding)->toMatchArray($response->json('data'));
});

test('empty onboarding update records the first entry time', function () {
    $this->travelTo(Carbon::parse('2026-07-31T10:00:00+08:00'));
    $user = User::factory()->create(['onboarding' => null]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding')
        ->assertOk()
        ->assertJsonPath('data.started_at', now()->toISOString())
        ->assertJsonPath('data.current_step', 0);
});

test('null onboarding selection clears the stored id', function () {
    $user = User::factory()->create([
        'onboarding' => [
            'selected_member_id' => 'member-id',
            'selected_medicine_id' => 'medicine-id',
        ],
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding', [
            'selected_member_id' => null,
            'selected_medicine_id' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.selected_member_id', null)
        ->assertJsonPath('data.selected_medicine_id', null);
});

test('completing onboarding applies the server completion state', function () {
    $this->travelTo(Carbon::parse('2026-07-31T10:05:00+08:00'));
    $user = User::factory()->create([
        'onboarding' => [
            'current_step' => 2,
            'started_at' => '2026-07-31T02:00:00.000000Z',
            'completed_at' => null,
            'skipped' => true,
            'selected_member_id' => 'member-id',
            'selected_medicine_id' => 'medicine-id',
        ],
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding', [
            'current_step' => 1,
            'skipped' => true,
            'completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.current_step', 3)
        ->assertJsonPath('data.completed_at', now()->toISOString())
        ->assertJsonPath('data.skipped', false);
});

test('completed false does not reopen completed onboarding', function () {
    $completedAt = '2026-07-31T02:05:00.000000Z';
    $user = User::factory()->create([
        'onboarding' => [
            'current_step' => 3,
            'started_at' => '2026-07-31T02:00:00.000000Z',
            'completed_at' => $completedAt,
            'skipped' => false,
        ],
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding', [
            'completed' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.current_step', 3)
        ->assertJsonPath('data.completed_at', $completedAt);
});

test('onboarding update fields use strict validation', function (array $payload, string $field) {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/onboarding', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonStructure(['errors' => [$field]]);
})->with([
    'step outside range' => [['current_step' => 4], 'current_step'],
    'step must be an integer' => [['current_step' => '2'], 'current_step'],
    'skipped must be boolean' => [['skipped' => 0], 'skipped'],
    'completed must be boolean' => [['completed' => 'true'], 'completed'],
    'member id must be string or null' => [['selected_member_id' => 123], 'selected_member_id'],
    'medicine id must be string or null' => [['selected_medicine_id' => 123], 'selected_medicine_id'],
    'started at is server managed' => [['started_at' => '2026-07-31T10:00:00+08:00'], 'started_at'],
    'completed at is server managed' => [['completed_at' => '2026-07-31T10:05:00+08:00'], 'completed_at'],
]);

test('updating onboarding requires an app token', function () {
    $this->postJson('/api/v1/users/me/onboarding', [
        'current_step' => 1,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});
