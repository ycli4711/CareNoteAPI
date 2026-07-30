<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cn_ai_models', 'capability')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->dropIndex(['capability', 'enabled']);
                $table->dropColumn('capability');
            });
        }

        if (Schema::hasColumn('cn_ai_models', 'provider_type')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->dropColumn('provider_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cn_ai_models', 'provider_type')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->string('provider_type', 60)->default('openai_text')->comment('兼容恢复的供应商适配器类型');
            });
        }

        if (! Schema::hasColumn('cn_ai_models', 'capability')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->string('capability', 60)->default('text_generation')->comment('兼容恢复的能力类型');
                $table->index(['capability', 'enabled']);
            });
        }
    }
};
