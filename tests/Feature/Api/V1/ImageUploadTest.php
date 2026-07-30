<?php

use App\Models\AdminUser;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.image_upload_disk', 'r2');
    Storage::fake('r2');
});

test('app user can upload multiple images', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/images', [
        'images' => [
            UploadedFile::fake()->image('medicine.jpg')->size(1024),
            UploadedFile::fake()->image('prescription.png')->size(2048),
        ],
    ], ['Accept' => 'application/json']);

    $response
        ->assertCreated()
        ->assertJsonCount(2, 'data.images')
        ->assertJsonStructure([
            'data' => [
                'images' => [
                    '*' => ['id', 'url', 'mime_type', 'size'],
                ],
            ],
            'meta' => ['request_id'],
        ]);

    expect(Storage::disk('r2')->allFiles())->toHaveCount(2);

    foreach ($response->json('data.images') as $image) {
        expect($image)->not->toHaveKey('path')
            ->and($image['id'])->toBeUuid();
    }
});

test('image upload requires an app token', function () {
    $this->post('/api/v1/images', [
        'images' => [UploadedFile::fake()->image('medicine.jpg')],
    ], ['Accept' => 'application/json'])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.UNAUTHENTICATED');
});

test('image upload validates image type size and maximum count', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mini-program', ['app:access'])->plainTextToken;

    $this->withToken($token)->post('/api/v1/images', [
        'images' => [UploadedFile::fake()->create('document.svg', 10, 'image/svg+xml')],
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonValidationErrors('images.0');

    $this->withToken($token)->post('/api/v1/images', [
        'images' => [UploadedFile::fake()->image('oversized.jpg')->size(10241)],
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images.0');

    $images = array_map(
        fn (int $index) => UploadedFile::fake()->image("image-{$index}.jpg"),
        range(1, 10),
    );

    $this->withToken($token)->post('/api/v1/images', [
        'images' => $images,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images');
});

test('administrator with media permission can upload images', function () {
    $this->seed(PermissionSeeder::class);
    $admin = AdminUser::factory()->create();
    $admin->givePermissionTo('admin.media.upload');

    $response = $this->actingAs($admin, 'admin')->post('/api/v1/admin/images', [
        'images' => [UploadedFile::fake()->image('medicine.webp')],
    ], ['Accept' => 'application/json']);

    $response
        ->assertCreated()
        ->assertJsonCount(1, 'data.images');

    expect(Storage::disk('r2')->allFiles())->toHaveCount(1)
        ->and($response->json('data.images.0'))->not->toHaveKey('path')
        ->and($response->json('data.images.0.id'))->toBeUuid();
});

test('administrator without media permission cannot upload images', function () {
    $this->seed(PermissionSeeder::class);
    $admin = AdminUser::factory()->create();

    $this->actingAs($admin, 'admin')->post('/api/v1/admin/images', [
        'images' => [UploadedFile::fake()->image('medicine.jpg')],
    ], ['Accept' => 'application/json'])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.FORBIDDEN');
});
