<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
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
            'admin.media.upload',
            'admin.ai.manage',
            'admin.accounts.manage',
            'admin.roles.manage',
            'system.health.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        Role::findOrCreate('super-admin', 'admin');
        Role::findOrCreate('administrator', 'admin')
            ->syncPermissions([
                'admin.access',
                'admin.dashboard.view',
                'admin.media.upload',
                'admin.ai.manage',
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
