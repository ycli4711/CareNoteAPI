<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates every CareNote cloud collection table', function (): void {
    $tables = [
        'users',
        'cn_families',
        'cn_family_members',
        'cn_medicines',
        'cn_medicine_versions',
        'cn_medication_plans',
        'cn_medication_records',
        'cn_health_logs',
        'cn_inventory_records',
        'cn_visits',
        'cn_follow_up_subscriptions',
        'cn_alarm_setup_logs',
        'cn_chat_sessions',
        'cn_ai_parse_logs',
        'cn_ai_rate_limit',
        'cn_events',
        'cn_invite_records',
        'cn_user_entitlements',
        'cn_entitlement_grants',
        'cn_quota_usage',
        'cn_user_streaks',
        'cn_weekly_reports',
        'cn_weekly_share_snapshots',
        'cn_documents',
        'cn_user_agreements',
        'cn_faq_categories',
        'cn_faq_items',
        'cn_changelogs',
    ];

    expect($tables)->toHaveCount(28);

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing migrated table [{$table}].");
    }
});

it('maps CareNote user fields without duplicating nickname and avatar columns', function (): void {
    expect(Schema::hasColumns('users', [
        'id',
        'display_name',
        'avatar_url',
        'gender',
        'tracking_enabled',
        'privacy_v1_1_seen',
        'invite_token',
        'theme_id',
        'onboarding',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumn('users', 'nickname'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'avatar'))->toBeFalse();
});

it('stores refresh token rotation metadata', function (): void {
    expect(Schema::hasColumns('personal_access_tokens', [
        'token_kind',
        'token_family_id',
        'revoked_at',
        'replaced_by_token_id',
    ]))->toBeTrue();
});

it('stores family dissolution state without deleting medical data', function (): void {
    expect(Schema::hasColumn('cn_families', 'dissolved_at'))->toBeTrue();
});

it('preserves legacy family ids in primary and reference columns', function (): void {
    $legacyFamilyId = 'legacy-cloud-family-id';
    $legacyMemberId = 'legacy-cloud-member-id';

    DB::table('cn_families')->insert([
        'id' => $legacyFamilyId,
        'name' => '旧家庭',
        'creator_openid' => 'legacy-openid',
        'member_openids' => json_encode(['legacy-openid'], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('cn_family_members')->insert([
        'id' => $legacyMemberId,
        'family_id' => $legacyFamilyId,
        'name' => '旧成员',
        'relation' => '本人',
        'linked_user_openid' => 'legacy-openid',
        'created_at' => now(),
    ]);

    expect(DB::table('cn_families')->where('id', $legacyFamilyId)->value('id'))
        ->toBe($legacyFamilyId)
        ->and(DB::table('cn_family_members')->where('id', $legacyMemberId)->value('family_id'))
        ->toBe($legacyFamilyId);
});

it('keeps embedded CareNote document fields as json columns', function (): void {
    $jsonColumns = [
        'cn_families' => ['member_openids'],
        'cn_family_members' => ['allergies', 'chronic_diseases'],
        'cn_medicines' => ['photo_urls', 'symptom_categories'],
        'cn_medication_plans' => ['remind_times'],
        'cn_medication_records' => ['consumed_versions', 'stock_shortage'],
        'cn_health_logs' => ['related_records'],
        'cn_inventory_records' => ['changes'],
        'cn_visits' => ['lab_reports', 'linked_plan_ids'],
        'cn_chat_sessions' => ['messages', 'context', 'plan_ids'],
        'cn_invite_records' => ['activations'],
        'cn_entitlement_grants' => ['reward'],
        'cn_user_streaks' => ['valid_record_dates', 'milestones_reached', 'milestones_claimed'],
        'cn_weekly_reports' => ['metrics'],
        'cn_weekly_share_snapshots' => ['metrics'],
        'cn_changelogs' => ['features'],
    ];

    foreach ($jsonColumns as $table => $columns) {
        expect(Schema::hasColumns($table, $columns))
            ->toBeTrue("Missing migrated JSON columns on [{$table}].");
    }
});

it('keeps the complete core medication field set', function (): void {
    expect(Schema::hasColumns('cn_medicines', [
        'family_id',
        'name',
        'specification',
        'manufacturer',
        'expiry_date',
        'opened_date',
        'opened_validity',
        'stock',
        'stock_unit',
        'stock_threshold',
        'stock_alert_silenced_until',
        'stock_alert_never',
        'stock_alert_last_sent_at',
        'stock_debt',
        'photo_urls',
        'cover_photo_url',
        'notes',
        'remark',
        'symptom_categories',
        'age_group',
        'gender_suitable',
        'expiry_alert_dismissed',
        'expiry_alert_dismissed_at',
        'version',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('cn_medication_records', [
        'family_id',
        'member_id',
        'medicine_id',
        'plan_id',
        'scheduled_time',
        'actual_time',
        'status',
        'dosage',
        'notes',
        'voice_url',
        'consumed_versions',
        'stock_shortage',
        'created_at',
    ]))->toBeTrue();
});

it('defines comments for every CareNote table and column', function (): void {
    $migration = require database_path('migrations/2026_07_30_180000_add_comments_to_carenote_tables.php');
    $reflection = new ReflectionClass($migration);
    $tableDefinitions = $reflection->getReflectionConstant('TABLES')->getValue();
    $userColumns = $reflection->getReflectionConstant('USER_COLUMNS')->getValue();

    expect($tableDefinitions)->toHaveCount(27);

    foreach ($tableDefinitions as $table => $definition) {
        expect($definition['comment'])->not->toBeEmpty()
            ->and(array_keys($definition['columns']))->toEqualCanonicalizing(
                Schema::getColumnListing($table),
            );

        foreach ($definition['columns'] as $comment) {
            expect($comment)->not->toBeEmpty();
        }
    }

    expect(array_keys($userColumns))->toEqualCanonicalizing([
        'gender',
        'tracking_enabled',
        'privacy_v1_1_seen',
        'invite_token',
        'theme_id',
        'onboarding',
    ]);

    foreach ($userColumns as $comment) {
        expect($comment)->not->toBeEmpty();
    }
});
