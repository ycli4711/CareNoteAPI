<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var AdminUser $admin */
        $admin = $request->user('admin');

        return Inertia::render('admin/dashboard', [
            'system' => [
                'api_version' => 'v1',
                'database' => config('database.default'),
                'storage' => filled(config('filesystems.disks.r2.endpoint'))
                    && filled(config('filesystems.disks.r2.bucket'))
                        ? 'configured'
                        : 'pending',
                'queue' => config('queue.default'),
            ],
            'roles' => $admin->getRoleNames()->values(),
        ]);
    }
}
