<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::redirect('/', '/admin/login')->name('admin.index');

    Route::get('dashboard', DashboardController::class)
        ->middleware(['auth:admin', 'verified', 'permission:admin.dashboard.view,admin'])
        ->name('dashboard');

    require __DIR__.'/admin-settings.php';
});
