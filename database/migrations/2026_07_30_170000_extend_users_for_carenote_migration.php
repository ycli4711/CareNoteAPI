<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('gender', 10)->nullable();
            $table->boolean('tracking_enabled')->nullable();
            $table->boolean('privacy_v1_1_seen')->nullable();
            $table->string('invite_token', 32)->nullable()->unique();
            $table->string('theme_id', 40)->nullable();
            $table->jsonb('onboarding')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['invite_token']);
            $table->dropColumn([
                'gender',
                'tracking_enabled',
                'privacy_v1_1_seen',
                'invite_token',
                'theme_id',
                'onboarding',
            ]);
        });
    }
};
