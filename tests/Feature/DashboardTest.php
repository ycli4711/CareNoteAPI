<?php

use App\Models\AdminUser;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = AdminUser::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('admin.dashboard.view', 'admin'));
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
