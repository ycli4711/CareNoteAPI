<?php

use App\Models\AdminUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public $withinTransaction = true;

    /**
     * Migrate an existing PostgreSQL installation from integer IDs to ULIDs.
     *
     * Fresh installations already receive ULID columns from the base migrations,
     * so this migration intentionally becomes a no-op for them.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_users') || ! $this->isIntegerColumn('admin_users', 'id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException(
                'Existing integer IDs can only be migrated automatically on PostgreSQL.'
            );
        }

        if (config('permission.teams')) {
            throw new RuntimeException(
                'The ULID data migration must be extended before migrating a teams-enabled permission schema.'
            );
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $roles = $tableNames['roles'];
        $permissions = $tableNames['permissions'];
        $modelHasRoles = $tableNames['model_has_roles'];
        $modelHasPermissions = $tableNames['model_has_permissions'];
        $roleHasPermissions = $tableNames['role_has_permissions'];
        $roleKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelKey = $columnNames['model_morph_key'];

        $adminIds = $this->makeIdMap('admin_users');
        $roleIds = $this->makeIdMap($roles);
        $permissionIds = $this->makeIdMap($permissions);
        $tokenIds = $this->makeIdMap('personal_access_tokens');

        $this->assertUlidCompatibleValues('users', 'id');
        $this->assertUlidCompatibleValues('user_identities', 'user_id');
        $this->assertUlidCompatibleValues('personal_access_tokens', 'tokenable_id');

        Schema::table('admin_users', function (Blueprint $table): void {
            $table->char('ulid_id', 26)->nullable();
        });
        Schema::table($roles, function (Blueprint $table): void {
            $table->char('ulid_id', 26)->nullable();
        });
        Schema::table($permissions, function (Blueprint $table): void {
            $table->char('ulid_id', 26)->nullable();
        });
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->char('ulid_id', 26)->nullable();
        });
        Schema::table('sessions', function (Blueprint $table): void {
            $table->char('ulid_user_id', 26)->nullable();
        });
        Schema::table($modelHasRoles, function (Blueprint $table): void {
            $table->char('ulid_role_id', 26)->nullable();
            $table->char('ulid_model_id', 26)->nullable();
        });
        Schema::table($modelHasPermissions, function (Blueprint $table): void {
            $table->char('ulid_permission_id', 26)->nullable();
            $table->char('ulid_model_id', 26)->nullable();
        });
        Schema::table($roleHasPermissions, function (Blueprint $table): void {
            $table->char('ulid_role_id', 26)->nullable();
            $table->char('ulid_permission_id', 26)->nullable();
        });

        $this->backfillPrimaryIds('admin_users', $adminIds);
        $this->backfillPrimaryIds($roles, $roleIds);
        $this->backfillPrimaryIds($permissions, $permissionIds);
        $this->backfillPrimaryIds('personal_access_tokens', $tokenIds);

        foreach (DB::table('sessions')->whereNotNull('user_id')->pluck('user_id')->unique() as $adminId) {
            DB::table('sessions')
                ->where('user_id', $adminId)
                ->update(['ulid_user_id' => $this->mappedId($adminIds, $adminId, 'session administrator')]);
        }

        foreach (DB::table($modelHasRoles)->get() as $row) {
            $this->assertAdminModelType($row->model_type);

            DB::table($modelHasRoles)
                ->where($roleKey, $row->{$roleKey})
                ->where($modelKey, $row->{$modelKey})
                ->where('model_type', $row->model_type)
                ->update([
                    'ulid_role_id' => $this->mappedId($roleIds, $row->{$roleKey}, 'role'),
                    'ulid_model_id' => $this->mappedId($adminIds, $row->{$modelKey}, 'administrator'),
                ]);
        }

        foreach (DB::table($modelHasPermissions)->get() as $row) {
            $this->assertAdminModelType($row->model_type);

            DB::table($modelHasPermissions)
                ->where($permissionKey, $row->{$permissionKey})
                ->where($modelKey, $row->{$modelKey})
                ->where('model_type', $row->model_type)
                ->update([
                    'ulid_permission_id' => $this->mappedId($permissionIds, $row->{$permissionKey}, 'permission'),
                    'ulid_model_id' => $this->mappedId($adminIds, $row->{$modelKey}, 'administrator'),
                ]);
        }

        foreach (DB::table($roleHasPermissions)->get() as $row) {
            DB::table($roleHasPermissions)
                ->where($permissionKey, $row->{$permissionKey})
                ->where($roleKey, $row->{$roleKey})
                ->update([
                    'ulid_permission_id' => $this->mappedId($permissionIds, $row->{$permissionKey}, 'permission'),
                    'ulid_role_id' => $this->mappedId($roleIds, $row->{$roleKey}, 'role'),
                ]);
        }

        $this->dropConstraint($modelHasRoles, "{$modelHasRoles}_pkey");
        $this->dropConstraint($modelHasRoles, "{$modelHasRoles}_{$roleKey}_foreign");
        $this->dropConstraint($modelHasPermissions, "{$modelHasPermissions}_pkey");
        $this->dropConstraint($modelHasPermissions, "{$modelHasPermissions}_{$permissionKey}_foreign");
        $this->dropConstraint($roleHasPermissions, "{$roleHasPermissions}_pkey");
        $this->dropConstraint($roleHasPermissions, "{$roleHasPermissions}_{$permissionKey}_foreign");
        $this->dropConstraint($roleHasPermissions, "{$roleHasPermissions}_{$roleKey}_foreign");
        $this->dropConstraint('admin_users', 'admin_users_pkey');
        $this->dropConstraint($roles, "{$roles}_pkey");
        $this->dropConstraint($permissions, "{$permissions}_pkey");
        $this->dropConstraint('personal_access_tokens', 'personal_access_tokens_pkey');

        $this->swapColumn('admin_users', 'id', 'ulid_id');
        $this->swapColumn($roles, 'id', 'ulid_id');
        $this->swapColumn($permissions, 'id', 'ulid_id');
        $this->swapColumn('personal_access_tokens', 'id', 'ulid_id');
        $this->swapColumn('sessions', 'user_id', 'ulid_user_id');
        $this->swapColumn($modelHasRoles, $roleKey, 'ulid_role_id');
        $this->swapColumn($modelHasRoles, $modelKey, 'ulid_model_id');
        $this->swapColumn($modelHasPermissions, $permissionKey, 'ulid_permission_id');
        $this->swapColumn($modelHasPermissions, $modelKey, 'ulid_model_id');
        $this->swapColumn($roleHasPermissions, $roleKey, 'ulid_role_id');
        $this->swapColumn($roleHasPermissions, $permissionKey, 'ulid_permission_id');

        $this->setNotNull('admin_users', 'id');
        $this->setNotNull($roles, 'id');
        $this->setNotNull($permissions, 'id');
        $this->setNotNull('personal_access_tokens', 'id');
        $this->setNotNull($modelHasRoles, $roleKey);
        $this->setNotNull($modelHasRoles, $modelKey);
        $this->setNotNull($modelHasPermissions, $permissionKey);
        $this->setNotNull($modelHasPermissions, $modelKey);
        $this->setNotNull($roleHasPermissions, $roleKey);
        $this->setNotNull($roleHasPermissions, $permissionKey);

        $this->addPrimary('admin_users', 'admin_users_pkey', ['id']);
        $this->addPrimary($roles, "{$roles}_pkey", ['id']);
        $this->addPrimary($permissions, "{$permissions}_pkey", ['id']);
        $this->addPrimary('personal_access_tokens', 'personal_access_tokens_pkey', ['id']);
        $this->addPrimary(
            $modelHasRoles,
            "{$modelHasRoles}_pkey",
            [$roleKey, $modelKey, 'model_type']
        );
        $this->addPrimary(
            $modelHasPermissions,
            "{$modelHasPermissions}_pkey",
            [$permissionKey, $modelKey, 'model_type']
        );
        $this->addPrimary(
            $roleHasPermissions,
            "{$roleHasPermissions}_pkey",
            [$permissionKey, $roleKey]
        );

        $this->addForeign($modelHasRoles, $roleKey, $roles);
        $this->addForeign($modelHasPermissions, $permissionKey, $permissions);
        $this->addForeign($roleHasPermissions, $permissionKey, $permissions);
        $this->addForeign($roleHasPermissions, $roleKey, $roles);

        $this->dropLegacyColumn('admin_users', 'id');
        $this->dropLegacyColumn($roles, 'id');
        $this->dropLegacyColumn($permissions, 'id');
        $this->dropLegacyColumn('personal_access_tokens', 'id');
        $this->dropLegacyColumn('sessions', 'user_id');
        $this->dropLegacyColumn($modelHasRoles, $roleKey);
        $this->dropLegacyColumn($modelHasRoles, $modelKey);
        $this->dropLegacyColumn($modelHasPermissions, $permissionKey);
        $this->dropLegacyColumn($modelHasPermissions, $modelKey);
        $this->dropLegacyColumn($roleHasPermissions, $roleKey);
        $this->dropLegacyColumn($roleHasPermissions, $permissionKey);

        $this->addIndex('sessions', 'sessions_user_id_index', ['user_id']);
        $this->addIndex(
            $modelHasRoles,
            'model_has_roles_model_id_model_type_index',
            [$modelKey, 'model_type']
        );
        $this->addIndex(
            $modelHasPermissions,
            'model_has_permissions_model_id_model_type_index',
            [$modelKey, 'model_type']
        );

        $this->dropConstraint('user_identities', 'user_identities_user_id_foreign');
        $this->alterToUlid('users', 'id');
        $this->alterToUlid('user_identities', 'user_id');
        $this->alterToUlid('personal_access_tokens', 'tokenable_id');
        $this->addForeign('user_identities', 'user_id', 'users');
    }

    /**
     * This primary-key rewrite is intentionally one-way. Restore a database
     * backup if the deployment itself must be rolled back.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'ULID primary-key migration cannot be reversed without restoring a database backup.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function makeIdMap(string $table): array
    {
        return DB::table($table)
            ->orderBy('id')
            ->pluck('id')
            ->mapWithKeys(fn (mixed $id): array => [(string) $id => (string) Str::ulid()])
            ->all();
    }

    /**
     * @param  array<string, string>  $ids
     */
    private function backfillPrimaryIds(string $table, array $ids): void
    {
        foreach ($ids as $legacyId => $ulid) {
            DB::table($table)->where('id', $legacyId)->update(['ulid_id' => $ulid]);
        }
    }

    /**
     * @param  array<string, string>  $ids
     */
    private function mappedId(array $ids, mixed $legacyId, string $label): string
    {
        return $ids[(string) $legacyId]
            ?? throw new RuntimeException("Cannot map missing {$label} ID [{$legacyId}] to a ULID.");
    }

    private function assertAdminModelType(string $modelType): void
    {
        if ($modelType !== AdminUser::class) {
            throw new RuntimeException(
                "Cannot safely migrate permission relation for unsupported model type [{$modelType}]."
            );
        }
    }

    private function assertUlidCompatibleValues(string $table, string $column): void
    {
        $invalid = DB::table($table)
            ->whereNotNull($column)
            ->whereRaw('char_length('.$this->identifier($column).') <> 26')
            ->exists();

        if ($invalid) {
            throw new RuntimeException("Column {$table}.{$column} contains a non-ULID value.");
        }
    }

    private function isIntegerColumn(string $table, string $column): bool
    {
        return in_array(Schema::getColumnType($table, $column), ['bigint', 'int8', 'integer', 'int4'], true);
    }

    private function swapColumn(string $table, string $legacyColumn, string $ulidColumn): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identifier($table),
            $this->identifier($legacyColumn),
            $this->identifier("legacy_{$legacyColumn}")
        ));
        DB::statement(sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identifier($table),
            $this->identifier($ulidColumn),
            $this->identifier($legacyColumn)
        ));
    }

    private function dropLegacyColumn(string $table, string $column): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP COLUMN %s',
            $this->identifier($table),
            $this->identifier("legacy_{$column}")
        ));
    }

    /**
     * @param  list<string>  $columns
     */
    private function addPrimary(string $table, string $name, array $columns): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s PRIMARY KEY (%s)',
            $this->identifier($table),
            $this->identifier($name),
            implode(', ', array_map($this->identifier(...), $columns))
        ));
    }

    private function addForeign(string $table, string $column, string $referencedTable): void
    {
        $name = "{$table}_{$column}_foreign";

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE CASCADE',
            $this->identifier($table),
            $this->identifier($name),
            $this->identifier($column),
            $this->identifier($referencedTable),
            $this->identifier('id')
        ));
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        DB::statement(sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->identifier($name),
            $this->identifier($table),
            implode(', ', array_map($this->identifier(...), $columns))
        ));
    }

    private function setNotNull(string $table, string $column): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s ALTER COLUMN %s SET NOT NULL',
            $this->identifier($table),
            $this->identifier($column)
        ));
    }

    private function alterToUlid(string $table, string $column): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s ALTER COLUMN %s TYPE CHAR(26) USING %s::CHAR(26)',
            $this->identifier($table),
            $this->identifier($column),
            $this->identifier($column)
        ));
    }

    private function dropConstraint(string $table, string $constraint): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
            $this->identifier($table),
            $this->identifier($constraint)
        ));
    }

    private function identifier(string $value): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new RuntimeException("Unsafe database identifier [{$value}].");
        }

        return '"'.$value.'"';
    }
};
