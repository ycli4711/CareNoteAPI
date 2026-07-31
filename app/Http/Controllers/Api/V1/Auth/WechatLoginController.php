<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\WechatLoginRequest;
use App\Http\Resources\Api\V1\WechatLoginResource;
use App\Services\Auth\WechatLoginService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class WechatLoginController extends Controller
{
    public function __invoke(
        WechatLoginRequest $request,
        WechatLoginService $service,
    ): JsonResponse {
        $result = $service->login($request->validated('code'));

        return ApiResponse::success(
            WechatLoginResource::make($result)->resolve($request),
            message: '登录成功。',
            request: $request,
        );
    }
}
