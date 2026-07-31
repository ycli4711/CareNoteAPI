<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCurrentUserRequest;
use App\Http\Resources\Api\V1\CurrentUserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateCurrentUserController extends Controller
{
    public function __invoke(UpdateCurrentUserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attributes = $request->validated();

        if (array_key_exists('nickname', $attributes)) {
            $attributes['display_name'] = $attributes['nickname'];
            unset($attributes['nickname']);
        }

        $user->update($attributes);

        return ApiResponse::success(
            CurrentUserResource::make($user)->resolve($request),
            request: $request,
        );
    }
}
