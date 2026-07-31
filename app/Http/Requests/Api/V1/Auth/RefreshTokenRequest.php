<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
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
            'refresh_token' => ['required', 'string', 'max:512'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $refreshToken = $this->input('refresh_token');

        if (is_string($refreshToken)) {
            $this->merge(['refresh_token' => trim($refreshToken)]);
        }
    }
}
