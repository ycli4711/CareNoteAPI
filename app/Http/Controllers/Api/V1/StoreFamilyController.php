<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFamilyRequest;
use App\Http\Resources\Api\V1\FamilyResource;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Api\ApiResponse;
use App\Support\Api\ApiSuccessCode;
use Illuminate\Http\JsonResponse;

class StoreFamilyController extends Controller
{
    public function __invoke(
        StoreFamilyRequest $request,
        FamilyService $families,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $family = $families->create($user, $request->validated('name'));

        return ApiResponse::success(
            FamilyResource::make($family)->resolve($request),
            code: ApiSuccessCode::Created,
            request: $request,
        );
    }
}
