<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin real-time business snapshot (SRS US-10).
 */
class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboard,
        protected LicenseService $license
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user === null || ! $user->hasRole(['Super Admin', 'Admin'])) {
            abort(403);
        }

        return view('admin.dashboard.index', [
            'stats' => $this->dashboard->snapshot(),
            'licenseDaysRemaining' => $this->license->daysUntilExpiry(),
        ]);
    }
}
