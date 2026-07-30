<?php

namespace App\Http\Requests\Admin;

use App\Models\AiChannel;
use App\Models\AiSceneModel;
use App\Services\Ai\AiAdapterRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAiSceneModelRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var AiSceneModel|null $sceneModel */
        $sceneModel = $this->route('sceneModel');

        return [
            'ai_channel_id' => ['required', 'ulid', 'exists:cn_ai_channels,id'],
            'scene_code' => ['required', Rule::in(array_keys((array) config('ai.scenes')))],
            'model' => [
                'required',
                'string',
                'max:120',
                Rule::unique('cn_ai_scene_models', 'model')
                    ->where(fn ($query) => $query
                        ->where('scene_code', $this->input('scene_code'))
                        ->where('ai_channel_id', $this->input('ai_channel_id')))
                    ->ignore($sceneModel),
            ],
            'priority' => ['required', 'integer', 'min:1', 'max:65535'],
            'enabled' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $channel = AiChannel::query()->find($this->input('ai_channel_id'));
                $scene = config('ai.scenes.'.$this->input('scene_code'));

                if (! $channel instanceof AiChannel || ! is_array($scene)) {
                    return;
                }

                if (! app(AiAdapterRegistry::class)->supports($channel, (string) $scene['capability'])) {
                    $validator->errors()->add(
                        'ai_channel_id',
                        '该渠道尚未实现当前场景的自动适配。',
                    );
                }
            },
        ];
    }
}
