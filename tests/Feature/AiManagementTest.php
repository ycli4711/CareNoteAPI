<?php

use App\Exceptions\Ai\AiProcessingException;
use App\Exceptions\Ai\AiQuotaExceededException;
use App\Models\AdminUser;
use App\Models\AiCallLog;
use App\Models\AiChannel;
use App\Models\AiRequest;
use App\Models\AiSceneModel;
use App\Models\SystemParameter;
use App\Models\User;
use App\Models\UserAiEntitlement;
use App\Models\UserAiUsage;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiQuotaService;
use App\Services\Ai\AiSceneResolver;
use Database\Seeders\AiManagementSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

function createAiTextRoute(int $priority, string $model): AiSceneModel
{
    $channel = AiChannel::query()->firstOrCreate(
        ['code' => 'test-qwen'],
        [
            'name' => '测试渠道',
            'provider_type' => 'qwen',
            'api_key' => 'test-secret',
            'base_url' => 'https://ai.example.test/v1',
            'timeout' => 5,
            'enabled' => true,
        ],
    );

    return AiSceneModel::query()->create([
        'ai_channel_id' => $channel->getKey(),
        'scene_code' => 'assistant_chat',
        'model' => $model,
        'priority' => $priority,
        'enabled' => true,
    ]);
}

test('ai configuration uses one flattened scene model table', function () {
    expect(Schema::hasTable('cn_ai_models'))->toBeFalse()
        ->and(Schema::hasTable('cn_ai_scenes'))->toBeFalse()
        ->and(Schema::hasColumns('cn_ai_scene_models', [
            'ai_channel_id',
            'scene_code',
            'model',
            'priority',
            'enabled',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('cn_ai_scene_models', 'ai_model_id'))->toBeFalse()
        ->and(Schema::hasColumn('cn_ai_scene_models', 'ai_scene_id'))->toBeFalse()
        ->and(Schema::hasTable('cn_user_ai_entitlements'))->toBeTrue();
});

test('administrator can render fixed scenes on the ai management page', function () {
    $this->seed([PermissionSeeder::class, AiManagementSeeder::class]);
    $admin = AdminUser::factory()->create();
    $admin->assignRole('administrator');
    $this->actingAs($admin, 'admin');

    $this->get('/admin/ai')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ai/index')
            ->has('channels')
            ->missing('models')
            ->has('scenes', 4)
            ->has('quotas', 4)
            ->has('referralRewards', 4)
            ->has('medicationSheetTiers', 2)
            ->has('stats')
            ->has('callLogs'));
});

test('administrator manages a scene model with the simplified fields', function () {
    $this->seed(PermissionSeeder::class);
    $admin = AdminUser::factory()->create();
    $admin->assignRole('administrator');
    $this->actingAs($admin, 'admin');
    $channel = AiChannel::query()->create([
        'code' => 'simple-openai',
        'name' => 'OpenAI',
        'provider_type' => 'openai',
        'base_url' => 'https://api.openai.com/v1',
        'enabled' => true,
    ]);

    $this->post('/admin/ai/scene-models', [
        'ai_channel_id' => $channel->getKey(),
        'scene_code' => 'medicine_ocr',
        'model' => 'gpt-4.1-mini',
        'priority' => 10,
        'enabled' => true,
    ])->assertRedirect('/admin/ai?tab=scenes');

    $route = AiSceneModel::query()->firstOrFail();
    expect($route->only([
        'ai_channel_id',
        'scene_code',
        'model',
        'priority',
        'enabled',
    ]))->toBe([
        'ai_channel_id' => $channel->getKey(),
        'scene_code' => 'medicine_ocr',
        'model' => 'gpt-4.1-mini',
        'priority' => 10,
        'enabled' => true,
    ]);
});

test('administrator saves the cloud aligned quota policy', function () {
    $this->seed(PermissionSeeder::class);
    $admin = AdminUser::factory()->create();
    $admin->assignRole('administrator');
    $this->actingAs($admin, 'admin');

    $this->post('/admin/ai/quota', [
        'quotas' => [
            ['scene' => 'assistant_chat', 'default_limit' => 30, 'early_bird_limit' => 100],
            ['scene' => 'medicine_ocr', 'default_limit' => 10, 'early_bird_limit' => 40],
            ['scene' => 'voice_plan', 'default_limit' => 10, 'early_bird_limit' => 40],
            ['scene' => 'medication_sheet', 'default_limit' => 1, 'early_bird_limit' => 1],
        ],
        'referral_rewards' => [
            ['code' => 'friend_login', 'inviter_amount' => 5, 'invitee_amount' => 2],
            ['code' => 'friend_first_medicine', 'inviter_amount' => 3, 'invitee_amount' => 1],
            ['code' => 'friend_first_plan', 'inviter_amount' => 10, 'invitee_amount' => 3],
            ['code' => 'friend_streak_3', 'inviter_amount' => 3, 'invitee_amount' => 1],
        ],
        'medication_sheet_tiers' => [
            ['min_invites' => 1, 'limit' => 2],
            ['min_invites' => 3, 'limit' => 5],
        ],
    ])->assertRedirect('/admin/ai?tab=quota');

    $policy = json_decode(
        SystemParameter::query()->where('key', 'ai.quota.policy')->value('value'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(data_get($policy, 'scenes.assistant_chat.early_bird_limit'))->toBe(100)
        ->and(data_get($policy, 'referral_rewards.friend_first_plan.inviter_amount'))->toBe(10)
        ->and(data_get($policy, 'medication_sheet_tiers.1.limit'))->toBe(5);
});

test('adapter is derived from channel and fixed scene', function () {
    $qwen = AiChannel::query()->create([
        'code' => 'qwen-auto',
        'name' => 'Qwen',
        'provider_type' => 'qwen',
        'api_key' => 'qwen-secret',
        'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
        'enabled' => true,
    ]);
    $openai = AiChannel::query()->create([
        'code' => 'openai-auto',
        'name' => 'OpenAI',
        'provider_type' => 'openai',
        'api_key' => 'openai-secret',
        'base_url' => 'https://api.openai.com/v1',
        'enabled' => true,
    ]);

    foreach ([$qwen, $openai] as $index => $channel) {
        AiSceneModel::query()->create([
            'ai_channel_id' => $channel->getKey(),
            'scene_code' => 'assistant_chat',
            'model' => "{$channel->code}-model",
            'priority' => ($index + 1) * 10,
            'enabled' => true,
        ]);
    }

    $configs = app(AiSceneResolver::class)->configs('assistant_chat');
    expect(collect($configs)->pluck('provider')->all())
        ->toBe(['qwen_text', 'openai_text']);
});

test('qwen requests disable thinking mode by default', function () {
    createAiTextRoute(10, 'qwen3.7-flash');
    $user = User::factory()->create();

    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ]),
    ]);

    app(AiManager::class)->complete(
        'assistant_chat',
        [['role' => 'user', 'content' => '你好']],
        $user,
        'qwen-no-thinking',
    );

    Http::assertSent(fn ($request) => $request['enable_thinking'] === false);
});

test('ai manager falls back by priority and only charges once', function () {
    createAiTextRoute(10, 'primary-model');
    $fallback = createAiTextRoute(20, 'fallback-model');
    $user = User::factory()->create();

    Http::fakeSequence()
        ->pushStatus(500)
        ->push([
            'choices' => [['message' => ['content' => '{"intent":"chat"}']]],
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 5],
        ]);

    $result = app(AiManager::class)->complete(
        'assistant_chat',
        [['role' => 'user', 'content' => '你好']],
        $user,
        'request-1',
    );

    expect($result->content)->toBe('{"intent":"chat"}')
        ->and(UserAiUsage::query()->value('used_count'))->toBe(1)
        ->and(AiCallLog::query()->count())->toBe(2)
        ->and(AiRequest::query()->value('final_ai_scene_model_id'))->toBe($fallback->getKey())
        ->and(AiRequest::query()->value('quota_status'))->toBe('charged');
    Http::assertSentCount(2);
});

test('ai manager refunds quota when every model fails', function () {
    createAiTextRoute(10, 'primary-model');
    createAiTextRoute(20, 'fallback-model');
    $user = User::factory()->create();
    Http::fakeSequence()->pushStatus(500)->pushStatus(503);

    expect(fn () => app(AiManager::class)->complete(
        'assistant_chat',
        [['role' => 'user', 'content' => '你好']],
        $user,
        'request-2',
    ))->toThrow(AiProcessingException::class);

    expect(UserAiUsage::query()->value('used_count'))->toBe(0)
        ->and(AiRequest::query()->value('quota_status'))->toBe('refunded')
        ->and(AiRequest::query()->value('status'))->toBe('failed');
});

test('ai quota follows cloud entitlement rules', function () {
    $ordinary = User::factory()->create();
    $earlyBird = User::factory()->create();
    $rewarded = User::factory()->create();
    $oneInvite = User::factory()->create();
    $threeInvites = User::factory()->create();

    UserAiEntitlement::query()->create([
        'user_id' => $earlyBird->getKey(),
        'early_bird' => true,
    ]);
    UserAiEntitlement::query()->create([
        'user_id' => $rewarded->getKey(),
        'bonuses' => ['assistant_chat' => 15],
    ]);
    UserAiEntitlement::query()->create([
        'user_id' => $oneInvite->getKey(),
        'successful_invites' => 1,
    ]);
    UserAiEntitlement::query()->create([
        'user_id' => $threeInvites->getKey(),
        'successful_invites' => 3,
    ]);

    $quota = app(AiQuotaService::class);

    expect($quota->summary($ordinary, 'assistant_chat')['limit'])->toBe(30)
        ->and($quota->summary($earlyBird, 'assistant_chat')['limit'])->toBe(100)
        ->and($quota->summary($rewarded, 'assistant_chat')['limit'])->toBe(45)
        ->and($quota->summary($ordinary, 'medication_sheet')['limit'])->toBe(1)
        ->and($quota->summary($oneInvite, 'medication_sheet')['limit'])->toBe(2)
        ->and($quota->summary($threeInvites, 'medication_sheet')['limit'])->toBe(5);
});

test('ai quota policy is read from the system parameter table', function () {
    createAiTextRoute(10, 'primary-model');
    $user = User::factory()->create();
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ]),
    ]);

    app(AiManager::class)->complete(
        'assistant_chat',
        [['role' => 'user', 'content' => '第一次']],
        $user,
        'quota-1',
    );

    SystemParameter::query()->updateOrCreate(
        ['key' => 'ai.quota.policy'],
        [
            'value' => json_encode([
                'scenes' => [
                    'assistant_chat' => [
                        'default_limit' => 1,
                        'early_bird_limit' => 1,
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'value_type' => 'json',
            'group' => 'ai_quota',
        ],
    );

    expect(fn () => app(AiManager::class)->complete(
        'assistant_chat',
        [['role' => 'user', 'content' => '第二次']],
        $user,
        'quota-2',
    ))->toThrow(AiQuotaExceededException::class);
});
