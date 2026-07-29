<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('display_name')->nullable();
            $table->text('avatar_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestampTz('last_active_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('user_identities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('user_id');
            $table->string('provider');
            $table->string('provider_subject');
            $table->string('union_id')->nullable()->index();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['provider', 'provider_subject']);
            $table->index(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_identities');
        Schema::dropIfExists('users');
    }
};
