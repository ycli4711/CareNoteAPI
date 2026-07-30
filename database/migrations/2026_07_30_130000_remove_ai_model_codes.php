<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cn_ai_call_logs', 'model_code')) {
            Schema::table('cn_ai_call_logs', function (Blueprint $table): void {
                $table->dropIndex(['model_code', 'created_at']);
                $table->dropColumn('model_code');
                $table->index(['model', 'created_at']);
            });
        }

        if (Schema::hasColumn('cn_ai_models', 'code')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cn_ai_models', 'code')) {
            Schema::table('cn_ai_models', function (Blueprint $table): void {
                $table->string('code', 80)->nullable()->comment('兼容恢复的模型编码');
            });
        }

        if (! Schema::hasColumn('cn_ai_call_logs', 'model_code')) {
            Schema::table('cn_ai_call_logs', function (Blueprint $table): void {
                $table->dropIndex(['model', 'created_at']);
                $table->string('model_code', 80)->nullable()->comment('兼容恢复的模型编码快照');
                $table->index(['model_code', 'created_at']);
            });
        }
    }
};
