<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn_system_parameters', function (Blueprint $table): void {
            $table->comment('CareNote统一系统参数表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->string('key', 120)->unique()->comment('参数键');
            $table->text('value')->nullable()->comment('参数值');
            $table->string('value_type', 20)->default('string')->comment('值类型：string、integer、boolean、json');
            $table->string('group', 60)->default('system')->comment('参数分组');
            $table->string('description', 500)->nullable()->comment('参数说明');
            $table->timestamps();
            $table->index(['group', 'key']);
        });

        Schema::create('cn_ai_channels', function (Blueprint $table): void {
            $table->comment('AI供应商渠道配置表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->string('code', 60)->unique()->comment('渠道编码');
            $table->string('name', 80)->comment('渠道名称');
            $table->string('provider_type', 60)->comment('供应商类型');
            $table->text('api_key')->nullable()->comment('加密存储的接口密钥');
            $table->string('base_url', 500)->nullable()->comment('接口基础地址');
            $table->unsignedSmallInteger('timeout')->default(60)->comment('请求超时秒数');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->json('options')->nullable()->comment('渠道扩展配置');
            $table->timestamps();
            $table->index(['provider_type', 'enabled']);
        });

        Schema::create('cn_ai_scene_models', function (Blueprint $table): void {
            $table->comment('AI场景模型配置表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->foreignUlid('ai_channel_id')->comment('所属渠道ID')->constrained('cn_ai_channels')->cascadeOnDelete();
            $table->string('scene_code', 80)->comment('固定业务场景编码');
            $table->string('model', 120)->comment('供应商模型名称');
            $table->unsignedSmallInteger('priority')->default(100)->comment('场景内优先级，数值越小越优先');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();
            $table->unique(['scene_code', 'ai_channel_id', 'model'], 'cn_ai_scene_model_unique');
            $table->index(['scene_code', 'enabled', 'priority']);
        });

        Schema::create('cn_ai_requests', function (Blueprint $table): void {
            $table->comment('AI业务请求表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->foreignUlid('user_id')->nullable()->comment('应用用户ID')->constrained('users')->nullOnDelete();
            $table->string('scene_code', 80)->comment('固定业务场景编码');
            $table->string('request_id', 80)->unique()->comment('业务请求ID');
            $table->string('idempotency_key', 120)->nullable()->comment('客户端幂等键');
            $table->string('status', 32)->comment('请求状态');
            $table->string('quota_status', 32)->default('not_charged')->comment('额度状态');
            $table->foreignUlid('final_ai_scene_model_id')->nullable()->comment('最终成功场景模型ID')->constrained('cn_ai_scene_models')->nullOnDelete();
            $table->unsignedInteger('duration_ms')->nullable()->comment('总耗时毫秒');
            $table->string('error_message', 500)->nullable()->comment('失败原因');
            $table->json('result_summary')->nullable()->comment('脱敏结果摘要');
            $table->timestamps();
            $table->unique(['user_id', 'scene_code', 'idempotency_key']);
            $table->index(['scene_code', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('cn_ai_call_logs', function (Blueprint $table): void {
            $table->comment('AI模型调用明细表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->foreignUlid('ai_request_id')->comment('AI业务请求ID')->constrained('cn_ai_requests')->cascadeOnDelete();
            $table->foreignUlid('ai_scene_model_id')->nullable()->comment('AI场景模型ID')->constrained('cn_ai_scene_models')->nullOnDelete();
            $table->string('scene_code', 80)->comment('场景编码快照');
            $table->string('channel_code', 60)->nullable()->comment('渠道编码快照');
            $table->string('model', 120)->nullable()->comment('供应商模型名称快照');
            $table->string('status', 32)->comment('调用状态');
            $table->unsignedSmallInteger('attempt')->default(1)->comment('模型尝试序号');
            $table->unsignedInteger('duration_ms')->nullable()->comment('调用耗时毫秒');
            $table->unsignedInteger('input_tokens')->nullable()->comment('输入Token数');
            $table->unsignedInteger('output_tokens')->nullable()->comment('输出Token数');
            $table->string('error_message', 500)->nullable()->comment('失败原因');
            $table->json('result_summary')->nullable()->comment('脱敏结果摘要');
            $table->timestamps();
            $table->index(['scene_code', 'status', 'created_at']);
            $table->index(['model', 'created_at']);
        });

        Schema::create('cn_user_ai_usages', function (Blueprint $table): void {
            $table->comment('用户AI场景额度用量表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->foreignUlid('user_id')->comment('应用用户ID')->constrained('users')->cascadeOnDelete();
            $table->string('scene_code', 80)->comment('固定业务场景编码');
            $table->string('period_type', 20)->comment('周期类型：daily、monthly、total');
            $table->string('period_key', 20)->comment('周期键');
            $table->unsignedInteger('used_count')->default(0)->comment('已使用次数');
            $table->timestamps();
            $table->unique(['user_id', 'scene_code', 'period_type', 'period_key'], 'cn_user_ai_usage_period_unique');
            $table->index(['scene_code', 'period_type', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn_user_ai_usages');
        Schema::dropIfExists('cn_ai_call_logs');
        Schema::dropIfExists('cn_ai_requests');
        Schema::dropIfExists('cn_ai_scene_models');
        Schema::dropIfExists('cn_ai_channels');
        Schema::dropIfExists('cn_system_parameters');
    }
};
