<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Resources\Api\V1\TokenPairResource;
use App\Services\Auth\TokenPairService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class RefreshTokenController extends Controller
{
    public function __invoke(
        RefreshTokenRequest $request,
        TokenPairService $service,
    ): JsonResponse {
        $tokens = $service->rotate($request->validated('refresh_token'));

        return ApiResponse::success(
            TokenPairResource::make($tokens)->resolve($request),
            message: 'Token 刷新成功。',
            request: $request,
        );
    }
}
