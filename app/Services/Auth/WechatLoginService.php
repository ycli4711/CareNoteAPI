<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AccountDisabledException;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WechatLoginService
{
    private const PROVIDER = 'wechat_mini_program';

    public function __construct(
        private readonly WechatMiniProgramClient $wechatClient,
        private readonly TokenPairService $tokenPairService,
    ) {}

    public function login(string $code): WechatLoginResult
    {
        $session = $this->wechatClient->exchangeCode($code);
        $isNew = false;

        try {
            $user = DB::transaction(function () use ($session, &$isNew): User {
                $identity = UserIdentity::query()
                    ->with('user')
                    ->where('provider', self::PROVIDER)
                    ->where('provider_subject', $session->openid)
                    ->first();

                if ($identity !== null) {
                    $this->updateExistingUser($identity->user);
                    $this->updateUnionId($identity, $session);

                    return $identity->user;
                }

                $user = User::query()->create([
                    'display_name' => 'CareNote 用户',
                    'avatar_url' => null,
                    'status' => 'active',
                    'tracking_enabled' => false,
                    'privacy_v1_1_seen' => null,
                    'last_active_at' => now(),
                ]);

                $user->identities()->create([
                    'provider' => self::PROVIDER,
                    'provider_subject' => $session->openid,
                    'union_id' => $session->unionId,
                ]);

                $isNew = true;

                return $user;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $identity = UserIdentity::query()
                ->with('user')
                ->where('provider', self::PROVIDER)
                ->where('provider_subject', $session->openid)
                ->first();

            if ($identity === null) {
                throw $exception;
            }

            $user = $identity->user;
            $this->updateExistingUser($user);
            $this->updateUnionId($identity, $session);
            $isNew = false;
        }

        $this->ensureActive($user);

        return new WechatLoginResult(
            user: $user->refresh(),
            tokens: $this->tokenPairService->issue($user),
            isNew: $isNew,
        );
    }

    private function updateExistingUser(User $user): void
    {
        $this->ensureActive($user);

        $user->update(['last_active_at' => now()]);
    }

    private function updateUnionId(UserIdentity $identity, WechatSession $session): void
    {
        if ($session->unionId !== null && $identity->union_id !== $session->unionId) {
            $identity->update(['union_id' => $session->unionId]);
        }
    }

    private function ensureActive(User $user): void
    {
        if ($user->status !== 'active') {
            throw new AccountDisabledException;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }
}
