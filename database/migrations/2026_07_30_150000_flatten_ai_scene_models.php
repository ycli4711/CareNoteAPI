<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cn_ai_models') || ! Schema::hasTable('cn_ai_scenes')) {
            return;
        }

        $this->addFlattenedColumns();
        $this->copyLegacyData();
        $this->dropLegacyRelations();
        $this->finalizeFlattenedSchema();
    }

    public function down(): void
    {
        // 旧结构包含已经废弃的模型名称、场景和参数层，扁平化后无法无损还原。
    }

    private function addFlattenedColumns(): void
    {
        Schema::table('cn_ai_scene_models', function (Blueprint $table): void {
            $table->ulid('ai_channel_id')->nullable();
            $table->string('scene_code', 80)->nullable();
            $table->string('model', 120)->nullable();
        });

        Schema::table('cn_ai_requests', function (Blueprint $table): void {
            $table->string('scene_code', 80)->nullable();
            $table->ulid('final_ai_scene_model_id')->nullable();
        });

        Schema::table('cn_ai_call_logs', function (Blueprint $table): void {
            $table->ulid('ai_scene_model_id')->nullable();
        });

        Schema::table('cn_user_ai_usages', function (Blueprint $table): void {
            $table->string('scene_code', 80)->nullable();
        });
    }

    private function copyLegacyData(): void
    {
        $routes = DB::table('cn_ai_scene_models as route')
            ->join('cn_ai_models as model', 'model.id', '=', 'route.ai_model_id')
            ->join('cn_ai_scenes as scene', 'scene.id', '=', 'route.ai_scene_id')
            ->select([
                'route.id',
                'model.ai_channel_id',
                'model.model',
                'scene.code as scene_code',
            ])
            ->get();

        foreach ($routes as $route) {
            DB::table('cn_ai_scene_models')->where('id', $route->id)->update([
                'ai_channel_id' => $route->ai_channel_id,
                'scene_code' => $route->scene_code,
                'model' => $route->model,
            ]);
        }

        $requests = DB::table('cn_ai_requests as request')
            ->join('cn_ai_scenes as scene', 'scene.id', '=', 'request.ai_scene_id')
            ->select([
                'request.id',
                'request.ai_scene_id',
                'request.final_ai_model_id',
                'scene.code as scene_code',
            ])
            ->get();

        foreach ($requests as $request) {
            $finalRouteId = $request->final_ai_model_id === null
                ? null
                : DB::table('cn_ai_scene_models')
                    ->where('ai_scene_id', $request->ai_scene_id)
                    ->where('ai_model_id', $request->final_ai_model_id)
                    ->value('id');

            DB::table('cn_ai_requests')->where('id', $request->id)->update([
                'scene_code' => $request->scene_code,
                'final_ai_scene_model_id' => $finalRouteId,
            ]);
        }

        $logs = DB::table('cn_ai_call_logs')->select([
            'id',
            'ai_model_id',
            'scene_code',
        ])->get();

        foreach ($logs as $log) {
            $routeId = $log->ai_model_id === null
                ? null
                : DB::table('cn_ai_scene_models')
                    ->where('ai_model_id', $log->ai_model_id)
                    ->where('scene_code', $log->scene_code)
                    ->value('id');

            DB::table('cn_ai_call_logs')->where('id', $log->id)->update([
                'ai_scene_model_id' => $routeId,
            ]);
        }

        $usages = DB::table('cn_user_ai_usages as usage')
            ->join('cn_ai_scenes as scene', 'scene.id', '=', 'usage.ai_scene_id')
            ->select(['usage.id', 'scene.code as scene_code'])
            ->get();

        foreach ($usages as $usage) {
            DB::table('cn_user_ai_usages')->where('id', $usage->id)->update([
                'scene_code' => $usage->scene_code,
            ]);
        }
    }

    private function dropLegacyRelations(): void
    {
        Schema::table('cn_ai_call_logs', function (Blueprint $table): void {
            $table->dropForeign(['ai_model_id']);
            $table->dropColumn(['ai_model_id', 'fallback_enabled']);
        });

        Schema::table('cn_ai_requests', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'ai_scene_id', 'idempotency_key']);
            $table->dropIndex(['ai_scene_id', 'status', 'created_at']);
            $table->dropForeign(['ai_scene_id']);
            $table->dropForeign(['final_ai_model_id']);
            $table->dropColumn(['ai_scene_id', 'final_ai_model_id']);
        });

        Schema::table('cn_user_ai_usages', function (Blueprint $table): void {
            $table->dropUnique('cn_user_ai_usage_period_unique');
            $table->dropIndex(['ai_scene_id', 'period_type', 'period_key']);
            $table->dropForeign(['ai_scene_id']);
            $table->dropColumn('ai_scene_id');
        });

        Schema::table('cn_ai_scene_models', function (Blueprint $table): void {
            $table->dropUnique(['ai_scene_id', 'ai_model_id']);
            $table->dropIndex(['ai_scene_id', 'enabled', 'priority']);
            $table->dropForeign(['ai_scene_id']);
            $table->dropForeign(['ai_model_id']);
            $table->dropColumn([
                'ai_scene_id',
                'ai_model_id',
                'fallback_enabled',
                'options',
            ]);
        });

        Schema::drop('cn_ai_models');
        Schema::drop('cn_ai_scenes');
    }

    private function finalizeFlattenedSchema(): void
    {
        Schema::table('cn_ai_scene_models', function (Blueprint $table): void {
            $table->ulid('ai_channel_id')->nullable(false)->change();
            $table->string('scene_code', 80)->nullable(false)->change();
            $table->string('model', 120)->nullable(false)->change();
            $table->foreign('ai_channel_id')->references('id')->on('cn_ai_channels')->cascadeOnDelete();
            $table->unique(['scene_code', 'ai_channel_id', 'model'], 'cn_ai_scene_model_unique');
            $table->index(['scene_code', 'enabled', 'priority']);
        });

        Schema::table('cn_ai_requests', function (Blueprint $table): void {
            $table->string('scene_code', 80)->nullable(false)->change();
            $table->foreign('final_ai_scene_model_id')->references('id')->on('cn_ai_scene_models')->nullOnDelete();
            $table->unique(['user_id', 'scene_code', 'idempotency_key']);
            $table->index(['scene_code', 'status', 'created_at']);
        });

        Schema::table('cn_ai_call_logs', function (Blueprint $table): void {
            $table->foreign('ai_scene_model_id')->references('id')->on('cn_ai_scene_models')->nullOnDelete();
        });

        Schema::table('cn_user_ai_usages', function (Blueprint $table): void {
            $table->string('scene_code', 80)->nullable(false)->change();
            $table->unique(['user_id', 'scene_code', 'period_type', 'period_key'], 'cn_user_ai_usage_period_unique');
            $table->index(['scene_code', 'period_type', 'period_key']);
        });
    }
};
