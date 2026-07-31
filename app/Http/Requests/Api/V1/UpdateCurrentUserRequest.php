<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\StrictBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCurrentUserRequest extends FormRequest
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
        return [
            'nickname' => ['sometimes', 'string', 'min:1', 'max:20'],
            'gender' => ['sometimes', 'string', Rule::in(['male', 'female', 'unset'])],
            'tracking_enabled' => ['sometimes', new StrictBoolean],
            'privacy_v1_1_seen' => ['sometimes', new StrictBoolean],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny([
                    'nickname',
                    'gender',
                    'tracking_enabled',
                    'privacy_v1_1_seen',
                ])) {
                    $validator->errors()->add('profile', '至少需要提供一个可更新字段。');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nickname') && is_string($this->input('nickname'))) {
            $this->merge([
                'nickname' => trim($this->input('nickname')),
            ]);
        }
    }
}
