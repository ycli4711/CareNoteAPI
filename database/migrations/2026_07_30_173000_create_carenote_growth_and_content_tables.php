<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn_events', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128)->nullable();
            $table->string('event_name');
            $table->string('user_id', 128);
            $table->string('session_id');
            $table->unsignedBigInteger('timestamp');
            $table->string('app_version');
            $table->string('ref')->nullable();
            $table->string('ref_user_id', 128)->nullable();
            $table->string('feature_module')->nullable();
            $table->string('source')->nullable();
            $table->string('quota_type')->nullable();
            $table->string('activation_type')->nullable();
            $table->string('reward_type')->nullable();
            $table->integer('step')->nullable();
            $table->integer('count')->nullable();
            $table->integer('item_count')->nullable();
            $table->integer('missing_field_count')->nullable();
            $table->string('status')->nullable();
            $table->string('record_status')->nullable();
            $table->string('week_start')->nullable();
            $table->string('share_target')->nullable();
            $table->integer('current_streak')->nullable();
            $table->integer('milestone')->nullable();
            $table->integer('last_streak')->nullable();
            $table->string('path')->nullable();
            $table->string('confidence_bucket')->nullable();
            $table->string('error_code')->nullable();

            $table->index(['user_id', 'event_name', 'timestamp']);
            $table->index(['event_name', 'timestamp']);
        });

        Schema::create('cn_invite_records', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128)->nullable();
            $table->string('inviter_openid', 128);
            $table->string('invitee_openid', 128);
            $table->string('invite_token', 32);
            $table->string('scene_path')->nullable();
            $table->string('status', 30);
            $table->jsonb('activations')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('archived_at')->nullable();

            $table->index(['inviter_openid', 'status', 'created_at']);
            $table->index(['invitee_openid', 'status']);
            $table->index('invite_token');
        });

        Schema::create('cn_user_entitlements', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->unsignedInteger('medicine_limit');
            $table->unsignedInteger('plan_limit');
            $table->unsignedInteger('family_member_limit');
            $table->unsignedInteger('ai_chat_monthly');
            $table->unsignedInteger('ocr_monthly');
            $table->unsignedInteger('ai_voice_basic_monthly');
            $table->unsignedInteger('medication_sheet_import_monthly');
            $table->boolean('advanced_monthly_report_enabled');
            $table->boolean('early_bird');
            $table->boolean('early_bird_capacity_plus_50')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique('openid');
        });

        Schema::create('cn_entitlement_grants', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('grant_type', 40);
            $table->jsonb('reward');
            $table->string('source_id')->nullable();
            $table->text('description');
            $table->timestampTz('created_at');

            $table->index(['openid', 'created_at']);
            $table->index(['openid', 'source_id']);
        });

        Schema::create('cn_quota_usage', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('quota_type', 60);
            $table->string('period', 20);
            $table->unsignedInteger('used');
            $table->timestampTz('updated_at');

            $table->unique(['openid', 'quota_type', 'period']);
        });

        Schema::create('cn_user_streaks', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->unsignedInteger('current_streak');
            $table->unsignedInteger('longest_streak');
            $table->string('last_record_date', 10);
            $table->jsonb('valid_record_dates');
            $table->jsonb('milestones_reached');
            $table->jsonb('milestones_claimed');
            $table->boolean('backfilled');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique('openid');
        });

        Schema::create('cn_weekly_reports', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('family_id', 64)->nullable();
            $table->timestampTz('week_start');
            $table->timestampTz('week_end');
            $table->string('week_key')->nullable();
            $table->jsonb('metrics');
            $table->boolean('viewed');
            $table->timestampTz('viewed_at')->nullable();
            $table->unsignedInteger('shared_count');
            $table->timestampTz('generated_at');
            $table->timestampTz('updated_at');

            $table->unique(['openid', 'week_key']);
            $table->index(['family_id', 'week_start']);
        });

        Schema::create('cn_weekly_share_snapshots', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('report_id', 64);
            $table->string('family_id', 64)->nullable();
            $table->timestampTz('week_start');
            $table->timestampTz('week_end');
            $table->string('week_key')->nullable();
            $table->jsonb('metrics');
            $table->unsignedInteger('view_count');
            $table->timestampTz('created_at')->nullable();
            $table->timestampTz('updated_at')->nullable();
            $table->timestampTz('last_viewed_at')->nullable();

            $table->unique('report_id');
        });

        Schema::create('cn_documents', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('type', 30);
            $table->string('title');
            $table->text('content');
            $table->string('version', 30);
            $table->timestampTz('update_date');
            $table->boolean('is_active');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['type', 'is_active']);
        });

        Schema::create('cn_user_agreements', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128)->nullable();
            $table->string('privacy_version', 30);
            $table->string('agreement_version', 30);
            $table->timestampTz('agreed_at');

            $table->index(['openid', 'agreed_at']);
        });

        Schema::create('cn_faq_categories', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('business_id')->unique();
            $table->string('title');
            $table->string('icon');
            $table->string('color', 40);
            $table->integer('order');
            $table->boolean('is_active');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['is_active', 'order']);
        });

        Schema::create('cn_faq_items', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('business_id')->unique();
            $table->string('category_id');
            $table->text('question');
            $table->text('answer');
            $table->integer('order');
            $table->boolean('is_active');
            $table->unsignedBigInteger('views')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['category_id', 'is_active', 'order']);
        });

        Schema::create('cn_changelogs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('version', 30)->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->timestampTz('release_date');
            $table->jsonb('features');
            $table->boolean('is_active');
            $table->boolean('is_highlighted')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['is_active', 'release_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn_changelogs');
        Schema::dropIfExists('cn_faq_items');
        Schema::dropIfExists('cn_faq_categories');
        Schema::dropIfExists('cn_user_agreements');
        Schema::dropIfExists('cn_documents');
        Schema::dropIfExists('cn_weekly_share_snapshots');
        Schema::dropIfExists('cn_weekly_reports');
        Schema::dropIfExists('cn_user_streaks');
        Schema::dropIfExists('cn_quota_usage');
        Schema::dropIfExists('cn_entitlement_grants');
        Schema::dropIfExists('cn_user_entitlements');
        Schema::dropIfExists('cn_invite_records');
        Schema::dropIfExists('cn_events');
    }
};
