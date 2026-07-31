<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOnboardingStateRequest;
use App\Http\Resources\Api\V1\OnboardingStateResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateOnboardingStateController extends Controller
{
    public function __invoke(UpdateOnboardingStateRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        $userId = $request->user()->getKey();
        $now = now()->toISOString();

        $user = DB::transaction(function () use ($attributes, $userId, $now): User {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $state = is_array($user->onboarding) ? $user->onboarding : [];

            $state += [
                'current_step' => 0,
                'started_at' => null,
                'completed_at' => null,
                'skipped' => false,
                'selected_member_id' => null,
                'selected_medicine_id' => null,
            ];

            if ($state['started_at'] === null) {
                $state['started_at'] = $now;
            }

            foreach ([
                'current_step',
                'skipped',
                'selected_member_id',
                'selected_medicine_id',
            ] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $state[$field] = $attributes[$field];
                }
            }

            if (($attributes['completed'] ?? false) === true) {
                $state['current_step'] = 3;
                $state['completed_at'] = $now;
                $state['skipped'] = false;
            }

            $user->update(['onboarding' => $state]);

            return $user;
        });

        return ApiResponse::success(
            OnboardingStateResource::make($user->onboarding)->resolve($request),
            request: $request,
        );
    }
}
