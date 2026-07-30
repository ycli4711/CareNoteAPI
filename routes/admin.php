<?php

use App\Http\Controllers\Admin\AiChannelController;
use App\Http\Controllers\Admin\AiConfigController;
use App\Http\Controllers\Admin\AiSceneModelController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::redirect('/', '/admin/login')->name('admin.index');

    Route::get('dashboard', DashboardController::class)
        ->middleware(['auth:admin', 'verified', 'permission:admin.dashboard.view,admin'])
        ->name('dashboard');

    Route::middleware(['auth:admin', 'verified', 'permission:admin.ai.manage,admin'])
        ->prefix('ai')
        ->name('ai.')
        ->group(function () {
            Route::get('/', [AiConfigController::class, 'index'])->name('index');
            Route::post('quota', [AiConfigController::class, 'updateQuota'])->name('quota.update');

            Route::post('channels', [AiChannelController::class, 'store'])->name('channels.store');
            Route::post('channels/{channel}', [AiChannelController::class, 'update'])->name('channels.update');
            Route::post('channels/{channel}/delete', [AiChannelController::class, 'destroy'])->name('channels.destroy');

            Route::post('scene-models', [AiSceneModelController::class, 'store'])->name('scene-models.store');
            Route::post('scene-models/{sceneModel}', [AiSceneModelController::class, 'update'])->name('scene-models.update');
            Route::post('scene-models/{sceneModel}/delete', [AiSceneModelController::class, 'destroy'])->name('scene-models.destroy');
        });

    require __DIR__.'/admin-settings.php';
});
