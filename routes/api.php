<?php

use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\ImageUploadController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('ping', PingController::class)->name('ping');

    Route::middleware(['auth:sanctum', 'app-user', 'abilities:app:access'])->group(function () {
        Route::get('me', CurrentUserController::class)->name('me');
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
