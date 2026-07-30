<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiChannelRequest;
use App\Models\AiChannel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AiChannelController extends Controller
{
    public function store(SaveAiChannelRequest $request): RedirectResponse
    {
        AiChannel::query()->create($this->attributes($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI渠道已创建。']);

        return to_route('ai.index', ['tab' => 'channels']);
    }

    public function update(SaveAiChannelRequest $request, AiChannel $channel): RedirectResponse
    {
        $channel->update($this->attributes($request, $channel));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI渠道已更新。']);

        return to_route('ai.index', ['tab' => 'channels']);
    }

    public function destroy(AiChannel $channel): RedirectResponse
    {
        if ($channel->sceneModels()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '该渠道仍有关联模型，不能删除。']);

            return back();
        }

        $channel->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI渠道已删除。']);

        return to_route('ai.index', ['tab' => 'channels']);
    }

    /** @return array<string, mixed> */
    private function attributes(SaveAiChannelRequest $request, ?AiChannel $channel = null): array
    {
        $validated = $request->validated();
        $attributes = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'provider_type' => $validated['provider_type'],
            'base_url' => $validated['base_url'],
            'timeout' => $validated['timeout'],
            'enabled' => $validated['enabled'],
        ];

        if ($validated['clear_api_key'] ?? false) {
            $attributes['api_key'] = null;
        } elseif (filled($validated['api_key'] ?? null)) {
            $attributes['api_key'] = $validated['api_key'];
        } elseif ($channel === null) {
            $attributes['api_key'] = null;
        }

        return $attributes;
    }
}
