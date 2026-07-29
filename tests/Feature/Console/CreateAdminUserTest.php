<?php

use App\Models\AdminUser;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('admin create command stores a verified admin with a role', function () {
    $this->artisan('admin:create', [
        '--name' => 'CareNote Admin',
        '--email' => 'admin@carenote.test',
        '--password' => 'StrongPass123!',
        '--role' => 'super-admin',
    ])->assertSuccessful();

    $admin = AdminUser::query()->where('email', 'admin@carenote.test')->firstOrFail();

    expect($admin->hasRole('super-admin'))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('admin create command rejects duplicate email', function () {
    AdminUser::factory()->create(['email' => 'admin@carenote.test']);

    $this->artisan('admin:create', [
        '--name' => 'Duplicate Admin',
        '--email' => 'admin@carenote.test',
        '--password' => 'StrongPass123!',
    ])->assertFailed();

    expect(AdminUser::query()->where('email', 'admin@carenote.test')->count())->toBe(1);
});
