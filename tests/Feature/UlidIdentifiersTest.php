<?php

use App\Models\AdminUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('application authentication and permission records use ulids', function () {
    $admin = AdminUser::factory()->create();
    $user = User::factory()->create();
    $identity = $user->identities()->create([
        'provider' => 'wechat',
        'provider_subject' => 'subject-1',
    ]);
    $permission = Permission::findOrCreate('admin.dashboard.view', 'admin');
    $role = Role::findOrCreate('administrator', 'admin');

    $role->givePermissionTo($permission);
    $admin->assignRole($role);

    $token = $user->createToken('test');

    expect(Str::isUlid($admin->getKey()))->toBeTrue()
        ->and(Str::isUlid($user->getKey()))->toBeTrue()
        ->and(Str::isUlid($identity->getKey()))->toBeTrue()
        ->and(Str::isUlid($permission->getKey()))->toBeTrue()
        ->and(Str::isUlid($role->getKey()))->toBeTrue()
        ->and(Str::isUlid($token->accessToken->getKey()))->toBeTrue()
        ->and(DB::table('model_has_roles')->value('model_id'))->toBe($admin->getKey())
        ->and(DB::table('role_has_permissions')->value('role_id'))->toBe($role->getKey())
        ->and(DB::table('role_has_permissions')->value('permission_id'))->toBe($permission->getKey());
});
