<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyService
{
    private const WECHAT_PROVIDER = 'wechat_mini_program';

    /** @return EloquentCollection<int, Family> */
    public function listFor(User $user): EloquentCollection
    {
        $openId = $this->openIdFor($user);
        $families = Family::query()
            ->whereNull('dissolved_at')
            ->whereJsonContains('member_openids', $openId)
            ->latest('created_at')
            ->get();

        return $this->hydrateResourceContext($families, $openId);
    }

    public function create(User $user, string $name): Family
    {
        $openId = $this->openIdFor($user);

        $family = DB::transaction(function () use ($user, $name, $openId): Family {
            $family = Family::query()->create([
                'name' => $name,
                'creator_openid' => $openId,
                'member_openids' => [$openId],
            ]);

            FamilyMember::query()->create([
                'family_id' => $family->getKey(),
                'name' => $user->display_name ?: '本人',
                'relation' => '本人',
                'avatar' => $user->avatar_url,
                'allergies' => [],
                'chronic_diseases' => [],
                'linked_user_openid' => $openId,
            ]);

            return $family;
        }, 3);

        return $this->hydrateOne($family, $openId);
    }

    public function updateName(User $user, Family $family, string $name): Family
    {
        $openId = $this->openIdFor($user);

        $family = DB::transaction(function () use ($family, $name, $openId): Family {
            /** @var Family $lockedFamily */
            $lockedFamily = Family::query()
                ->whereKey($family->getKey())
                ->whereNull('dissolved_at')
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($openId, $lockedFamily->member_openids ?? [], true)) {
                throw new AuthorizationException;
            }

            $lockedFamily->update(['name' => $name]);

            return $lockedFamily;
        }, 3);

        return $this->hydrateOne($family, $openId);
    }

    /**
     * @return array{family_id: string, family_dissolved: bool}
     */
    public function leave(User $user, Family $family): array
    {
        $openId = $this->openIdFor($user);

        return DB::transaction(function () use ($family, $openId): array {
            /** @var Family $lockedFamily */
            $lockedFamily = Family::query()
                ->whereKey($family->getKey())
                ->whereNull('dissolved_at')
                ->lockForUpdate()
                ->firstOrFail();

            $remainingOpenIds = array_values(array_filter(
                $lockedFamily->member_openids ?? [],
                static fn (mixed $memberOpenId): bool => $memberOpenId !== $openId,
            ));

            if (count($remainingOpenIds) === count($lockedFamily->member_openids ?? [])) {
                throw new AuthorizationException;
            }

            FamilyMember::query()
                ->where('family_id', $lockedFamily->getKey())
                ->where('linked_user_openid', $openId)
                ->update(['linked_user_openid' => null]);

            $familyDissolved = $remainingOpenIds === [];
            $attributes = ['member_openids' => $remainingOpenIds];

            if ($familyDissolved) {
                $attributes['dissolved_at'] = now();
            } elseif (hash_equals((string) $lockedFamily->creator_openid, $openId)) {
                $attributes['creator_openid'] = $remainingOpenIds[0];
            }

            $lockedFamily->update($attributes);

            return [
                'family_id' => (string) $lockedFamily->getKey(),
                'family_dissolved' => $familyDissolved,
            ];
        }, 3);
    }

    public function openIdFor(User $user): string
    {
        $openId = $user->identities()
            ->where('provider', self::WECHAT_PROVIDER)
            ->value('provider_subject');

        if (! is_string($openId) || $openId === '') {
            throw new AuthorizationException;
        }

        return $openId;
    }

    /**
     * @param  EloquentCollection<int, Family>  $families
     * @return EloquentCollection<int, Family>
     */
    private function hydrateResourceContext(
        EloquentCollection $families,
        string $currentOpenId,
    ): EloquentCollection {
        $openIds = $families
            ->flatMap(fn (Family $family): array => $family->member_openids ?? [])
            ->unique()
            ->values();

        $usersByOpenId = $this->usersByOpenId($openIds);

        foreach ($families as $family) {
            $family->setResourceContext(
                $currentOpenId,
                collect($family->member_openids ?? [])
                    ->map(fn (string $openId): ?User => $usersByOpenId->get($openId))
                    ->filter()
                    ->values(),
            );
        }

        return $families;
    }

    private function hydrateOne(Family $family, string $currentOpenId): Family
    {
        $families = new EloquentCollection([$family]);

        return $this->hydrateResourceContext($families, $currentOpenId)->firstOrFail();
    }

    /**
     * @param  Collection<int, string>  $openIds
     * @return Collection<string, User>
     */
    private function usersByOpenId(Collection $openIds): Collection
    {
        if ($openIds->isEmpty()) {
            return collect();
        }

        return UserIdentity::query()
            ->with('user.identities')
            ->where('provider', self::WECHAT_PROVIDER)
            ->whereIn('provider_subject', $openIds)
            ->get()
            ->mapWithKeys(fn (UserIdentity $identity): array => [
                $identity->provider_subject => $identity->user,
            ]);
    }
}
