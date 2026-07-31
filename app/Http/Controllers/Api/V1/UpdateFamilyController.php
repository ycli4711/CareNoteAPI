<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateFamilyRequest;
use App\Http\Resources\Api\V1\FamilyResource;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UpdateFamilyController extends Controller
{
    public function __invoke(
        UpdateFamilyRequest $request,
        Family $family,
        FamilyService $families,
    ): JsonResponse {
        Gate::authorize('update', $family);

        /** @var User $user */
        $user = $request->user();
        $family = $families->updateName(
            $user,
            $family,
            $request->validated('name'),
        );

        return ApiResponse::success(
            FamilyResource::make($family)->resolve($request),
            request: $request,
        );
    }
}
