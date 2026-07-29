<?php

use App\Models\AdminUser;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('guest is redirected to the admin login', function () {
    $this->get('/admin/dashboard')->assertRedirect('/admin/login');
});

test('app user cannot enter the admin dashboard', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->get('/admin/dashboard')->assertRedirect('/admin/login');
});

test('admin without dashboard permission is forbidden', function () {
    $this->actingAs(AdminUser::factory()->create(), 'admin');

    $this->get('/admin/dashboard')->assertForbidden();
});

test('administrator with permission can render the dashboard', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole('administrator');
    $this->actingAs($admin, 'admin');

    $this->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('system')
            ->has('roles'));
});

test('super admin receives the gate override', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin, 'admin');

    $this->get('/admin/dashboard')->assertOk();
});
