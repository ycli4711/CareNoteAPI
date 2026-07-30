<?php

namespace App\Http\Requests\Admin;

use App\Models\AiChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAiChannelRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var AiChannel|null $channel */
        $channel = $this->route('channel');

        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('cn_ai_channels', 'code')->ignore($channel)],
            'name' => ['required', 'string', 'max:80'],
            'provider_type' => ['required', Rule::in(['qwen', 'openai'])],
            'api_key' => ['nullable', 'string', 'max:2000'],
            'clear_api_key' => ['sometimes', 'boolean'],
            'base_url' => ['required', 'url:http,https', 'max:500'],
            'timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
