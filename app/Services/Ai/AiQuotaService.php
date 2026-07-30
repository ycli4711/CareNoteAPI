<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiQuotaExceededException;
use App\Models\User;
use App\Models\UserAiEntitlement;
use App\Models\UserAiUsage;
use Illuminate\Support\Facades\DB;

class AiQuotaService
{
    public function __construct(private readonly AiQuotaPolicyService $policy) {}

    /** @return array{period: string, period_key: string, limit: int, used: int, remaining: int, can_use: bool} */
    public function summary(User $user, string $sceneCode): array
    {
        [$period, $limit, $periodKey] = $this->settings($user, $sceneCode);
        $used = (int) UserAiUsage::query()
            ->where('user_id', $user->getKey())
            ->where('scene_code', $sceneCode)
            ->where('period_type', $period)
            ->where('period_key', $periodKey)
            ->value('used_count');

        return $this->buildSummary($period, $periodKey, $limit, $used);
    }

    public function consume(User $user, string $sceneCode): void
    {
        DB::transaction(function () use ($user, $sceneCode): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            [$period, $limit, $periodKey] = $this->settings($user, $sceneCode);
            $usage = $this->usageForUpdate($user, $sceneCode, $period, $periodKey);

            if ($usage->used_count >= $limit) {
                throw new AiQuotaExceededException;
            }

            $usage->increment('used_count');
        });
    }

    public function refund(User $user, string $sceneCode): void
    {
        DB::transaction(function () use ($user, $sceneCode): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            [$period, , $periodKey] = $this->settings($user, $sceneCode);
            $usage = $this->usageForUpdate($user, $sceneCode, $period, $periodKey);

            if ($usage->used_count > 0) {
                $usage->decrement('used_count');
            }
        });
    }

    /** @return array{string, int, string} */
    private function settings(User $user, string $sceneCode): array
    {
        $settings = $this->policy->scene($sceneCode);
        $entitlement = UserAiEntitlement::query()->where('user_id', $user->getKey())->first();
        $baseLimit = $entitlement?->early_bird
            ? $settings['early_bird_limit']
            : $settings['default_limit'];
        $bonuses = (array) ($entitlement?->bonuses ?? []);
        $limit = $baseLimit + max(0, (int) ($bonuses[$sceneCode] ?? 0));

        if ($sceneCode === 'medication_sheet') {
            $limit = max(
                $limit,
                $this->policy->medicationSheetTierLimit(
                    max(0, (int) ($entitlement?->successful_invites ?? 0)),
                ),
            );
        }

        return [$settings['period'], $limit, $this->periodKey($settings['period'])];
    }

    private function usageForUpdate(
        User $user,
        string $sceneCode,
        string $period,
        string $periodKey,
    ): UserAiUsage {
        $usage = UserAiUsage::query()
            ->where('user_id', $user->getKey())
            ->where('scene_code', $sceneCode)
            ->where('period_type', $period)
            ->where('period_key', $periodKey)
            ->lockForUpdate()
            ->first();

        return $usage ?? UserAiUsage::query()->create([
            'user_id' => $user->getKey(),
            'scene_code' => $sceneCode,
            'period_type' => $period,
            'period_key' => $periodKey,
            'used_count' => 0,
        ]);
    }

    private function periodKey(string $period): string
    {
        return match ($period) {
            'daily' => now()->format('Y-m-d'),
            'monthly' => now()->format('Y-m'),
            default => 'total',
        };
    }

    /** @return array{period: string, period_key: string, limit: int, used: int, remaining: int, can_use: bool} */
    private function buildSummary(string $period, string $periodKey, int $limit, int $used): array
    {
        return [
            'period' => $period,
            'period_key' => $periodKey,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'can_use' => $used < $limit,
        ];
    }
}
