<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn_families', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->string('creator_openid', 128);
            $table->jsonb('member_openids');
            $table->string('invite_code', 32)->nullable();
            $table->timestampTz('invite_code_expires')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index('creator_openid');
            $table->index('invite_code');
        });

        Schema::create('cn_family_members', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('name');
            $table->string('relation');
            $table->text('avatar')->nullable();
            $table->timestampTz('birthday')->nullable();
            $table->jsonb('allergies')->nullable();
            $table->jsonb('chronic_diseases')->nullable();
            $table->string('linked_user_openid', 128)->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at')->nullable();

            $table->index(['family_id', 'created_at']);
            $table->index('linked_user_openid');
        });

        Schema::create('cn_medicines', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('name');
            $table->string('specification');
            $table->string('manufacturer')->nullable();
            $table->timestampTz('expiry_date')->nullable();
            $table->timestampTz('opened_date')->nullable();
            $table->unsignedInteger('opened_validity')->nullable();
            $table->decimal('stock', 14, 4);
            $table->string('stock_unit', 40);
            $table->decimal('stock_threshold', 14, 4)->nullable();
            $table->timestampTz('stock_alert_silenced_until')->nullable();
            $table->boolean('stock_alert_never')->nullable();
            $table->timestampTz('stock_alert_last_sent_at')->nullable();
            $table->decimal('stock_debt', 14, 4)->nullable();
            $table->jsonb('photo_urls')->nullable();
            $table->text('cover_photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->text('remark')->nullable();
            $table->jsonb('symptom_categories')->nullable();
            $table->string('age_group', 20)->nullable();
            $table->string('gender_suitable', 20)->nullable();
            $table->boolean('expiry_alert_dismissed')->nullable();
            $table->timestampTz('expiry_alert_dismissed_at')->nullable();
            $table->unsignedInteger('version');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['family_id', 'created_at']);
            $table->index(['family_id', 'expiry_date']);
            $table->index(['family_id', 'stock']);
        });

        Schema::create('cn_medicine_versions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('medicine_id', 64);
            $table->unsignedInteger('version_number');
            $table->decimal('stock', 14, 4);
            $table->timestampTz('expiry_date');
            $table->timestampTz('opened_date')->nullable();
            $table->unsignedInteger('opened_validity')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('name');
            $table->string('specification');
            $table->string('manufacturer')->nullable();
            $table->string('change_reason');
            $table->boolean('is_current');
            $table->boolean('is_expired');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at')->nullable();

            $table->unique(['medicine_id', 'version_number']);
            $table->index(['family_id', 'is_expired']);
            $table->index(['medicine_id', 'expiry_date']);
        });

        Schema::create('cn_medication_plans', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('creator_openid', 128)->nullable();
            $table->string('member_id', 64);
            $table->string('medicine_id', 64);
            $table->string('plan_name')->nullable();
            $table->decimal('dosage', 14, 4);
            $table->string('dosage_unit', 40);
            $table->string('frequency');
            $table->jsonb('remind_times');
            $table->timestampTz('start_date');
            $table->timestampTz('end_date')->nullable();
            $table->boolean('before_meal')->nullable();
            $table->text('remark')->nullable();
            $table->boolean('is_active');
            $table->string('source_visit_id', 64)->nullable();
            $table->timestampTz('alarm_setup_prompted_at')->nullable();
            $table->unsignedInteger('alarm_setup_count')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['family_id', 'is_active']);
            $table->index(['member_id', 'is_active']);
            $table->index(['medicine_id', 'is_active']);
            $table->index('source_visit_id');
        });

        Schema::create('cn_medication_records', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('member_id', 64);
            $table->string('medicine_id', 64);
            $table->string('plan_id', 64);
            $table->timestampTz('scheduled_time');
            $table->timestampTz('actual_time')->nullable();
            $table->string('status', 30);
            $table->decimal('dosage', 14, 4);
            $table->text('notes')->nullable();
            $table->text('voice_url')->nullable();
            $table->jsonb('consumed_versions')->nullable();
            $table->jsonb('stock_shortage')->nullable();
            $table->timestampTz('created_at');

            $table->index(['family_id', 'scheduled_time']);
            $table->index(['member_id', 'scheduled_time']);
            $table->index(['plan_id', 'scheduled_time']);
            $table->index(['status', 'scheduled_time']);
        });

        Schema::create('cn_health_logs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('member_id', 64);
            $table->string('log_type', 20);
            $table->text('content')->nullable();
            $table->text('media_url')->nullable();
            $table->jsonb('related_records')->nullable();
            $table->timestampTz('created_at');

            $table->index(['family_id', 'created_at']);
            $table->index(['member_id', 'created_at']);
        });

        Schema::create('cn_inventory_records', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->timestampTz('inventory_date');
            $table->string('operator_openid', 128);
            $table->string('operator_name')->nullable();
            $table->string('medicine_id', 64);
            $table->string('medicine_name');
            $table->string('status', 30);
            $table->jsonb('changes');
            $table->text('note')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at')->nullable();

            $table->index(['family_id', 'inventory_date']);
            $table->index(['medicine_id', 'inventory_date']);
            $table->index('operator_openid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn_inventory_records');
        Schema::dropIfExists('cn_health_logs');
        Schema::dropIfExists('cn_medication_records');
        Schema::dropIfExists('cn_medication_plans');
        Schema::dropIfExists('cn_medicine_versions');
        Schema::dropIfExists('cn_medicines');
        Schema::dropIfExists('cn_family_members');
        Schema::dropIfExists('cn_families');
    }
};
