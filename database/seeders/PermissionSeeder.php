<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the initial administrator roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'admin.dashboard.view',
            'admin.accounts.manage',
            'admin.roles.manage',
            'system.health.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        Role::findOrCreate('super-admin', 'admin');
        Role::findOrCreate('administrator', 'admin')
            ->syncPermissions(['admin.access', 'admin.dashboard.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
