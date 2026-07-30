<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAiQuotaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quotas' => ['required', 'array'],
            'quotas.*.scene' => ['required', 'string', Rule::in(array_keys((array) config('ai.scenes')))],
            'quotas.*.default_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'quotas.*.early_bird_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'referral_rewards' => ['required', 'array'],
            'referral_rewards.*.code' => [
                'required',
                'string',
                Rule::in(array_keys((array) config('ai.quota_policy.referral_rewards'))),
            ],
            'referral_rewards.*.inviter_amount' => ['required', 'integer', 'min:0', 'max:1000000'],
            'referral_rewards.*.invitee_amount' => ['required', 'integer', 'min:0', 'max:1000000'],
            'medication_sheet_tiers' => ['required', 'array', 'size:2'],
            'medication_sheet_tiers.*.min_invites' => ['required', 'integer', Rule::in([1, 3])],
            'medication_sheet_tiers.*.limit' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
