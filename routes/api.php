<?php

use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\Auth\WechatLoginController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\ImageUploadController;
use App\Http\Controllers\Api\V1\LeaveFamilyController;
use App\Http\Controllers\Api\V1\ListFamiliesController;
use App\Http\Controllers\Api\V1\PingController;
use App\Http\Controllers\Api\V1\StoreFamilyController;
use App\Http\Controllers\Api\V1\UpdateAvatarController;
use App\Http\Controllers\Api\V1\UpdateCurrentUserController;
use App\Http\Controllers\Api\V1\UpdateFamilyController;
use App\Http\Controllers\Api\V1\UpdateOnboardingStateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('ping', PingController::class)->name('ping');
    Route::post('auth/wechat/login', WechatLoginController::class)
        ->middleware('throttle:wechat-login')
        ->name('auth.wechat.login');
    Route::post('auth/token/refresh', RefreshTokenController::class)
        ->middleware('throttle:token-refresh')
        ->name('auth.token.refresh');

    Route::middleware(['auth:sanctum', 'app-user', 'abilities:app:access'])->group(function () {
        Route::post('auth/logout', LogoutController::class)->name('auth.logout');
        Route::get('users/me', CurrentUserController::class)->name('users.me');
        Route::post('users/me', UpdateCurrentUserController::class)->name('users.me.update');
        Route::post('users/me/avatar', UpdateAvatarController::class)
            ->name('users.me.avatar.update');
        Route::post('users/me/onboarding', UpdateOnboardingStateController::class)
            ->name('users.me.onboarding.update');
        Route::get('families', ListFamiliesController::class)->name('families.index');
        Route::post('families', StoreFamilyController::class)->name('families.store');
        Route::post('families/{family}', UpdateFamilyController::class)
            ->name('families.update');
        Route::post('families/{family}/leave', LeaveFamilyController::class)
            ->name('families.leave');
        Route::post('images', ImageUploadController::class)->name('images.store');
    });

    Route::post('admin/images', ImageUploadController::class)
        ->middleware([
            'web',
            'auth:admin',
            'verified',
            'permission:admin.media.upload,admin',
        ])
        ->name('admin.images.store');
});
