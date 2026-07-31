<?php

use App\Jobs\DeleteStoredAvatar;
use App\Models\User;
use App\Services\Media\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.image_upload_disk', 'r2');
    Storage::fake('r2');
});

test('app user can upload and replace their avatar', function () {
    Queue::fake();
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('avatar.webp')->size(1024),
    ], ['Accept' => 'application/json']);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('code', 'COMMON.OK')
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => ['avatar_url'],
            'errors',
            'meta' => ['request_id'],
        ]);

    $avatarUrl = $response->json('data.avatar_url');

    expect($user->refresh()->avatar_url)->toBe($avatarUrl)
        ->and(Storage::disk('r2')->allFiles("avatars/{$user->id}"))->toHaveCount(1);

    Queue::assertNothingPushed();
});

test('replacing a managed avatar queues deletion of the old file', function () {
    Queue::fake();
    $user = User::factory()->create();
    $oldPath = "avatars/{$user->id}/2026/07/old-avatar.jpg";
    Storage::disk('r2')->put($oldPath, 'old-avatar');
    $user->update(['avatar_url' => Storage::disk('r2')->url($oldPath)]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('new-avatar.png'),
    ], ['Accept' => 'application/json'])
        ->assertOk();

    Queue::assertPushed(
        DeleteStoredAvatar::class,
        fn (DeleteStoredAvatar $job): bool => $job->path === $oldPath
            && $job->userId === $user->id,
    );
});

test('replacing an external avatar does not queue file deletion', function () {
    Queue::fake();
    $user = User::factory()->create([
        'avatar_url' => 'https://third-party.example/avatar.jpg',
    ]);
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('new-avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk();

    Queue::assertNothingPushed();
});

test('avatar cleanup job only deletes files from the matching user directory', function () {
    $user = User::factory()->create();
    $ownPath = "avatars/{$user->id}/2026/07/old-avatar.jpg";
    $otherPath = 'avatars/another-user/2026/07/avatar.jpg';
    Storage::disk('r2')->put($ownPath, 'own-avatar');
    Storage::disk('r2')->put($otherPath, 'other-avatar');
    $service = app(ImageStorageService::class);

    (new DeleteStoredAvatar($otherPath, $user->id))->handle($service);
    Storage::disk('r2')->assertExists($otherPath);

    (new DeleteStoredAvatar($ownPath, $user->id))->handle($service);
    Storage::disk('r2')->assertMissing($ownPath);
});

test('avatar upload rejects unsupported image types with 415', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'),
    ], ['Accept' => 'application/json'])
        ->assertStatus(415)
        ->assertJsonPath('code', 'COMMON.UNSUPPORTED_MEDIA_TYPE');
});

test('avatar upload rejects files larger than five megabytes with 413', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('avatar.jpg')->size(5121),
    ], ['Accept' => 'application/json'])
        ->assertStatus(413)
        ->assertJsonPath('code', 'COMMON.PAYLOAD_TOO_LARGE');
});

test('avatar upload requires multipart form data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/users/me/avatar', [
            'file' => 'not-a-multipart-file',
        ])
        ->assertStatus(415)
        ->assertJsonPath('code', 'COMMON.UNSUPPORTED_MEDIA_TYPE');
});

test('avatar upload requires a file', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)
        ->withHeader('Content-Type', 'multipart/form-data')
        ->post('/api/v1/users/me/avatar', [], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('avatar upload requires an app token', function () {
    $this->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});

test('new avatar is removed when updating the user fails', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;
    User::updating(function (): void {
        throw new RuntimeException('Database update failed.');
    });

    $this->withToken($token)->post('/api/v1/users/me/avatar', [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertInternalServerError();

    expect(Storage::disk('r2')->allFiles())->toBeEmpty();
});
