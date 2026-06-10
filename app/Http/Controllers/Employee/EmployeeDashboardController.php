<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use App\Services\Employee\EmployeeDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employee self-service home dashboard.
 */
class EmployeeDashboardController extends Controller
{
    use ResolvesLinkedEmployee;

    public function __construct(
        protected EmployeeDashboardService $dashboard
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);
        $monthInput = $request->query('month');
        $month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : now()->startOfMonth();

        return view('employee.dashboard.index', [
            'employee' => $employee,
            'summary' => $this->dashboard->summary($employee, $month),
            'selectedMonth' => $month->format('Y-m'),
        ]);
    }
}
