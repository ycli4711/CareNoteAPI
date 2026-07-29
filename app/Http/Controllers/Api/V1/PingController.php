<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'service' => config('app.name'),
            'api_version' => 'v1',
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ], request: $request);
    }
}
