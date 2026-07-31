<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function update(User $user, Family $family): bool
    {
        $openId = $this->openId($user);

        return $openId !== null
            && $family->dissolved_at === null
            && in_array($openId, $family->member_openids ?? [], true);
    }

    public function leave(User $user, Family $family): bool
    {
        $openId = $this->openId($user);

        return $openId !== null
            && $family->dissolved_at === null
            && in_array($openId, $family->member_openids ?? [], true);
    }

    private function openId(User $user): ?string
    {
        $subject = $user->identities()
            ->where('provider', 'wechat_mini_program')
            ->value('provider_subject');

        return is_string($subject) ? $subject : null;
    }
}
