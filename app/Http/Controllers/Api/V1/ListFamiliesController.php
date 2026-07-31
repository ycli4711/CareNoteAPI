<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FamilyResource;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListFamiliesController extends Controller
{
    public function __invoke(Request $request, FamilyService $families): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'items' => FamilyResource::collection($families->listFor($user))
                ->resolve($request),
        ], request: $request);
    }
}
