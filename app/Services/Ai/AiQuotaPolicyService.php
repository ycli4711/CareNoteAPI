<?php

namespace App\Services\Ai;

use App\Services\SystemParameterService;

class AiQuotaPolicyService
{
    public function __construct(private readonly SystemParameterService $parameters) {}

    /** @return array<string, mixed> */
    public function policy(): array
    {
        $defaults = (array) config('ai.quota_policy', []);
        $stored = $this->parameters->json('ai.quota.policy');

        return array_replace_recursive($defaults, $stored);
    }

    /** @param array<string, mixed> $policy */
    public function save(array $policy): void
    {
        $this->parameters->set(
            'ai.quota.policy',
            $policy,
            'json',
            'ai_quota',
            'AI场景基础额度、早鸟额度、邀请奖励与用药单邀请阶梯。',
        );
    }

    /** @return array{period: string, default_limit: int, early_bird_limit: int} */
    public function scene(string $sceneCode): array
    {
        $policy = $this->policy();
        $scene = (array) data_get($policy, "scenes.{$sceneCode}", []);
        $period = (string) ($policy['period'] ?? 'monthly');

        return [
            'period' => in_array($period, ['daily', 'monthly', 'total'], true) ? $period : 'monthly',
            'default_limit' => max(0, (int) ($scene['default_limit'] ?? 0)),
            'early_bird_limit' => max(0, (int) ($scene['early_bird_limit'] ?? 0)),
        ];
    }

    public function medicationSheetTierLimit(int $successfulInvites): int
    {
        $tiers = collect((array) data_get($this->policy(), 'medication_sheet_tiers', []))
            ->sortByDesc(fn (array $tier): int => (int) ($tier['min_invites'] ?? 0));

        $tier = $tiers->first(
            fn (array $tier): bool => $successfulInvites >= (int) ($tier['min_invites'] ?? 0),
        );

        return max(0, (int) ($tier['limit'] ?? 0));
    }
}
