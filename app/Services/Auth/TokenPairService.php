<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\RefreshTokenExpiredException;
use App\Exceptions\Auth\RefreshTokenInvalidException;
use App\Exceptions\Auth\SessionRevokedException;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TokenPairService
{
    private const ACCESS_ABILITY = 'app:access';

    private const ACCESS_KIND = 'access';

    private const REFRESH_ABILITY = 'auth:refresh';

    private const REFRESH_KIND = 'refresh';

    public function issue(User $user): TokenPair
    {
        return DB::transaction(
            fn (): TokenPair => $this->createPair($user, (string) Str::ulid()),
        );
    }

    public function rotate(string $plainTextRefreshToken): TokenPair
    {
        $result = DB::transaction(function () use ($plainTextRefreshToken): TokenPair|Throwable {
            $token = $this->findTokenForUpdate($plainTextRefreshToken);

            if ($token === null || ! $this->isRefreshToken($token)) {
                return new RefreshTokenInvalidException;
            }

            if ($token->revoked_at !== null) {
                $this->revokeFamily((string) $token->token_family_id);

                return new SessionRevokedException;
            }

            if ($token->expires_at === null || $token->expires_at->isPast()) {
                return new RefreshTokenExpiredException;
            }

            $user = $token->tokenable;

            if (! $user instanceof User || $token->token_family_id === null) {
                return new RefreshTokenInvalidException;
            }

            if ($user->status !== 'active') {
                return new AccountDisabledException;
            }

            $familyId = (string) $token->token_family_id;
            $this->revokeFamily($familyId);
            $pair = $this->createPair($user, $familyId);

            $replacement = PersonalAccessToken::findToken($pair->refreshToken);
            $token->forceFill([
                'replaced_by_token_id' => $replacement?->getKey(),
            ])->save();

            return $pair;
        });

        if ($result instanceof Throwable) {
            throw $result;
        }

        return $result;
    }

    public function revokeCurrentSession(PersonalAccessToken $token): void
    {
        if ($token->token_family_id === null) {
            $token->delete();

            return;
        }

        $this->revokeFamily((string) $token->token_family_id);
    }

    private function createPair(User $user, string $familyId): TokenPair
    {
        $now = CarbonImmutable::now();
        $accessExpiresIn = (int) config(
            'services.wechat_mini_program.access_token_ttl_seconds',
            86400,
        );
        $refreshExpiresIn = (int) config(
            'services.wechat_mini_program.refresh_token_ttl_seconds',
            2592000,
        );
        $accessExpiresAt = $now->addSeconds($accessExpiresIn);
        $refreshExpiresAt = $now->addSeconds($refreshExpiresIn);

        $access = $user->createToken(
            'mini-program-access',
            [self::ACCESS_ABILITY],
            $accessExpiresAt,
        );
        $access->accessToken->forceFill([
            'token_kind' => self::ACCESS_KIND,
            'token_family_id' => $familyId,
        ])->save();

        $refresh = $user->createToken(
            'mini-program-refresh',
            [self::REFRESH_ABILITY],
            $refreshExpiresAt,
        );
        $refresh->accessToken->forceFill([
            'token_kind' => self::REFRESH_KIND,
            'token_family_id' => $familyId,
        ])->save();

        return new TokenPair(
            accessToken: $access->plainTextToken,
            refreshToken: $refresh->plainTextToken,
            accessExpiresAt: $accessExpiresAt,
            refreshExpiresAt: $refreshExpiresAt,
            accessExpiresIn: $accessExpiresIn,
            refreshExpiresIn: $refreshExpiresIn,
        );
    }

    private function findTokenForUpdate(string $plainTextToken): ?PersonalAccessToken
    {
        if (! str_contains($plainTextToken, '|')) {
            return PersonalAccessToken::query()
                ->where('token', hash('sha256', $plainTextToken))
                ->lockForUpdate()
                ->first();
        }

        [$id, $token] = explode('|', $plainTextToken, 2);
        $accessToken = PersonalAccessToken::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if ($accessToken === null) {
            return null;
        }

        return hash_equals($accessToken->token, hash('sha256', $token))
            ? $accessToken
            : null;
    }

    private function isRefreshToken(PersonalAccessToken $token): bool
    {
        return $token->token_kind === self::REFRESH_KIND
            && $token->can(self::REFRESH_ABILITY);
    }

    private function revokeFamily(string $familyId): void
    {
        if ($familyId === '') {
            return;
        }

        $now = CarbonImmutable::now();

        PersonalAccessToken::query()
            ->where('token_family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now]);

        PersonalAccessToken::query()
            ->where('token_family_id', $familyId)
            ->where('token_kind', self::ACCESS_KIND)
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->update(['expires_at' => $now]);
    }
}
