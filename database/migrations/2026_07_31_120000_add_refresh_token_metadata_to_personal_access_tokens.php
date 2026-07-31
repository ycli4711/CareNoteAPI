<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->string('token_kind', 20)->default('access')->after('name');
            $table->ulid('token_family_id')->nullable()->after('token_kind');
            $table->timestamp('revoked_at')->nullable()->after('expires_at');
            $table->ulid('replaced_by_token_id')->nullable()->after('revoked_at');

            $table->index(['token_family_id', 'token_kind']);
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['token_family_id', 'token_kind']);
            $table->dropIndex(['revoked_at']);
            $table->dropColumn([
                'token_kind',
                'token_family_id',
                'revoked_at',
                'replaced_by_token_id',
            ]);
        });
    }
};
