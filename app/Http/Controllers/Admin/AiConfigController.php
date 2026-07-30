<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiQuotaRequest;
use App\Models\AiCallLog;
use App\Models\AiChannel;
use App\Models\AiRequest;
use App\Models\AiSceneModel;
use App\Services\Ai\AiQuotaPolicyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiConfigController extends Controller
{
    public function index(AiQuotaPolicyService $quotaPolicy): Response
    {
        return Inertia::render('admin/ai/index', [
            'channels' => $this->channels(),
            'scenes' => $this->scenes(),
            'quotas' => $this->quotas($quotaPolicy),
            'referralRewards' => $this->referralRewards($quotaPolicy),
            'medicationSheetTiers' => $this->medicationSheetTiers($quotaPolicy),
            'stats' => $this->stats(),
            'callLogs' => $this->callLogs(),
        ]);
    }

    public function updateQuota(
        SaveAiQuotaRequest $request,
        AiQuotaPolicyService $quotaPolicy,
    ): RedirectResponse {
        $validated = $request->validated();
        $defaults = $quotaPolicy->policy();
        $scenes = collect($validated['quotas'])->mapWithKeys(
            fn (array $quota): array => [
                $quota['scene'] => [
                    'default_limit' => $quota['default_limit'],
                    'early_bird_limit' => $quota['early_bird_limit'],
                ],
            ],
        )->all();
        $rewardDefaults = (array) ($defaults['referral_rewards'] ?? []);
        $rewards = collect($validated['referral_rewards'])->mapWithKeys(
            fn (array $reward): array => [
                $reward['code'] => [
                    'name' => data_get($rewardDefaults, "{$reward['code']}.name", $reward['code']),
                    'scene' => data_get($rewardDefaults, "{$reward['code']}.scene"),
                    'inviter_amount' => $reward['inviter_amount'],
                    'invitee_amount' => $reward['invitee_amount'],
                ],
            ],
        )->all();

        $quotaPolicy->save([
            'period' => 'monthly',
            'scenes' => $scenes,
            'referral_rewards' => $rewards,
            'medication_sheet_tiers' => $validated['medication_sheet_tiers'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI次数限制已保存。']);

        return to_route('ai.index', ['tab' => 'quota']);
    }

    /** @return array<int, array<string, mixed>> */
    private function channels(): array
    {
        return AiChannel::query()->orderBy('code')->get()->map(fn (AiChannel $channel): array => [
            'id' => $channel->getKey(),
            'code' => $channel->code,
            'name' => $channel->name,
            'provider_type' => $channel->provider_type,
            'has_api_key' => filled($channel->api_key),
            'api_key_masked' => $this->maskSecret($channel->api_key),
            'base_url' => $channel->base_url,
            'timeout' => $channel->timeout,
            'enabled' => $channel->enabled,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function scenes(): array
    {
        $routes = AiSceneModel::query()
            ->with('channel')
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->groupBy('scene_code');

        return collect((array) config('ai.scenes'))
            ->map(fn (array $scene, string $code): array => [
                'code' => $code,
                'name' => $scene['name'],
                'description' => $scene['description'] ?? null,
                'routes' => $routes->get($code, collect())
                    ->map(fn (AiSceneModel $route): array => [
                        'id' => $route->getKey(),
                        'ai_channel_id' => $route->ai_channel_id,
                        'channel_name' => $route->channel?->name,
                        'model' => $route->model,
                        'priority' => $route->priority,
                        'enabled' => $route->enabled,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{scene: string, name: string, default_limit: int, early_bird_limit: int}> */
    private function quotas(AiQuotaPolicyService $quotaPolicy): array
    {
        return collect((array) config('ai.scenes'))->map(function (array $defaults, string $code) use ($quotaPolicy): array {
            $settings = $quotaPolicy->scene($code);

            return [
                'scene' => $code,
                'name' => $defaults['name'],
                'default_limit' => $settings['default_limit'],
                'early_bird_limit' => $settings['early_bird_limit'],
            ];
        })->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function referralRewards(AiQuotaPolicyService $quotaPolicy): array
    {
        $scenes = (array) config('ai.scenes');

        return collect((array) data_get($quotaPolicy->policy(), 'referral_rewards', []))
            ->map(fn (array $reward, string $code): array => [
                'code' => $code,
                'name' => $reward['name'],
                'scene' => $reward['scene'],
                'scene_name' => data_get($scenes, "{$reward['scene']}.name", $reward['scene']),
                'inviter_amount' => (int) $reward['inviter_amount'],
                'invitee_amount' => (int) $reward['invitee_amount'],
            ])->values()->all();
    }

    /** @return array<int, array{min_invites: int, limit: int}> */
    private function medicationSheetTiers(AiQuotaPolicyService $quotaPolicy): array
    {
        return collect((array) data_get($quotaPolicy->policy(), 'medication_sheet_tiers', []))
            ->map(fn (array $tier): array => [
                'min_invites' => (int) $tier['min_invites'],
                'limit' => (int) $tier['limit'],
            ])->sortBy('min_invites')->values()->all();
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $today = now()->startOfDay();

        return [
            'requests' => AiRequest::query()->where('created_at', '>=', $today)->count(),
            'succeeded' => AiRequest::query()->where('created_at', '>=', $today)->where('status', 'succeeded')->count(),
            'failed' => AiRequest::query()->where('created_at', '>=', $today)->where('status', 'failed')->count(),
            'fallbacks' => AiCallLog::query()->where('created_at', '>=', $today)->where('attempt', '>', 1)->count(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function callLogs(): array
    {
        return AiCallLog::query()->latest()->limit(50)->get()->map(fn (AiCallLog $log): array => [
            'id' => $log->getKey(),
            'scene_code' => $log->scene_code,
            'channel_code' => $log->channel_code,
            'model' => $log->model,
            'status' => $log->status,
            'attempt' => $log->attempt,
            'duration_ms' => $log->duration_ms,
            'input_tokens' => $log->input_tokens,
            'output_tokens' => $log->output_tokens,
            'error_message' => $log->error_message,
            'created_at' => $log->created_at?->toDateTimeString(),
        ])->all();
    }

    private function maskSecret(?string $secret): ?string
    {
        if (blank($secret)) {
            return null;
        }

        return mb_strlen($secret) <= 8
            ? str_repeat('*', mb_strlen($secret))
            : mb_substr($secret, 0, 4).'********'.mb_substr($secret, -4);
    }
}
