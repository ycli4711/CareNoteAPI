<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\StrictBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentStep = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_int($value) || $value < 0 || $value > 3) {
                $fail("{$attribute} 必须是 0、1、2、3 中的一个整数。");
            }
        };

        return [
            'current_step' => ['sometimes', $currentStep],
            'skipped' => ['sometimes', new StrictBoolean],
            'selected_member_id' => ['sometimes', 'nullable', 'string'],
            'selected_medicine_id' => ['sometimes', 'nullable', 'string'],
            'completed' => ['sometimes', new StrictBoolean],
            'started_at' => ['prohibited'],
            'completed_at' => ['prohibited'],
        ];
    }
}
