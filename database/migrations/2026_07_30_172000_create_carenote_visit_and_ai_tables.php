<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn_visits', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128)->nullable();
            $table->string('family_id', 64);
            $table->string('member_id', 64);
            $table->timestampTz('visit_date');
            $table->string('visit_type', 30);
            $table->string('hospital')->nullable();
            $table->string('department')->nullable();
            $table->string('doctor')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('diagnosis_note')->nullable();
            $table->text('doctor_advice')->nullable();
            $table->text('follow_up_note')->nullable();
            $table->jsonb('lab_reports')->nullable();
            $table->string('previous_visit_id', 64)->nullable();
            $table->timestampTz('follow_up_date')->nullable();
            $table->boolean('follow_up_reminded')->nullable();
            $table->jsonb('linked_plan_ids')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['family_id', 'visit_date']);
            $table->index(['member_id', 'visit_date']);
            $table->index('previous_visit_id');
            $table->index('follow_up_date');
        });

        Schema::create('cn_follow_up_subscriptions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('family_id', 64);
            $table->string('visit_id', 64);
            $table->string('member_id', 64);
            $table->timestampTz('follow_up_date');
            $table->string('symptoms_snapshot');
            $table->string('hospital_snapshot');
            $table->string('member_name_snapshot');
            $table->string('tmpl_id');
            $table->string('status', 30);
            $table->timestampTz('sent_at')->nullable();
            $table->text('error_msg')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['status', 'follow_up_date']);
            $table->index(['family_id', 'visit_id']);
            $table->index('openid');
        });

        Schema::create('cn_alarm_setup_logs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('family_id', 64);
            $table->string('plan_id', 64);
            $table->string('member_id', 64);
            $table->string('openid', 128);
            $table->string('status', 30);
            $table->text('error_message')->nullable();
            $table->timestampTz('created_at');

            $table->index(['family_id', 'created_at']);
            $table->index(['plan_id', 'created_at']);
            $table->index('openid');
        });

        Schema::create('cn_chat_sessions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128)->nullable();
            $table->string('client_id');
            $table->text('summary');
            $table->jsonb('messages');
            $table->jsonb('context');
            $table->string('plan_id', 64)->nullable();
            $table->jsonb('plan_ids')->nullable();
            $table->string('step')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique(['openid', 'client_id']);
            $table->index(['openid', 'updated_at']);
        });

        Schema::create('cn_ai_parse_logs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('feature');
            $table->string('status', 30);
            $table->decimal('confidence', 8, 6)->nullable();
            $table->string('error_code')->nullable();
            $table->timestampTz('created_at');

            $table->index(['openid', 'created_at']);
            $table->index(['feature', 'status', 'created_at']);
        });

        Schema::create('cn_ai_rate_limit', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('openid', 128);
            $table->string('date', 10);
            $table->unsignedInteger('count');

            $table->unique(['openid', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn_ai_rate_limit');
        Schema::dropIfExists('cn_ai_parse_logs');
        Schema::dropIfExists('cn_chat_sessions');
        Schema::dropIfExists('cn_alarm_setup_logs');
        Schema::dropIfExists('cn_follow_up_subscriptions');
        Schema::dropIfExists('cn_visits');
    }
};
