<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiSceneModelRequest;
use App\Models\AiSceneModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AiSceneModelController extends Controller
{
    public function store(SaveAiSceneModelRequest $request): RedirectResponse
    {
        AiSceneModel::query()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => '场景模型已创建。']);

        return to_route('ai.index', ['tab' => 'scenes']);
    }

    public function update(
        SaveAiSceneModelRequest $request,
        AiSceneModel $sceneModel,
    ): RedirectResponse {
        $sceneModel->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => '场景模型已更新。']);

        return to_route('ai.index', ['tab' => 'scenes']);
    }

    public function destroy(AiSceneModel $sceneModel): RedirectResponse
    {
        $sceneModel->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => '场景模型已删除。']);

        return to_route('ai.index', ['tab' => 'scenes']);
    }
}
