<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn_user_ai_entitlements', function (Blueprint $table): void {
            $table->comment('用户AI权益同步表');
            $table->ulid('id')->primary()->comment('主键ID');
            $table->foreignUlid('user_id')->unique()->comment('应用用户ID')->constrained('users')->cascadeOnDelete();
            $table->boolean('early_bird')->default(false)->comment('是否早鸟用户');
            $table->unsignedInteger('successful_invites')->default(0)->comment('成功邀请人数');
            $table->json('bonuses')->nullable()->comment('各AI场景累计奖励次数');
            $table->timestampTz('synced_at')->nullable()->comment('云端权益同步时间');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn_user_ai_entitlements');
    }
};
