<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cn_families', function (Blueprint $table): void {
            $table->timestampTz('dissolved_at')
                ->nullable()
                ->after('invite_code_expires')
                ->comment('家庭解散时间');

            $table->index('dissolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('cn_families', function (Blueprint $table): void {
            $table->dropIndex(['dissolved_at']);
            $table->dropColumn('dissolved_at');
        });
    }
};
