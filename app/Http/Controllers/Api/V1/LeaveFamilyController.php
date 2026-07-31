<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeaveFamilyController extends Controller
{
    public function __invoke(
        Request $request,
        Family $family,
        FamilyService $families,
    ): JsonResponse {
        Gate::authorize('leave', $family);

        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(
            $families->leave($user, $family),
            request: $request,
        );
    }
}
