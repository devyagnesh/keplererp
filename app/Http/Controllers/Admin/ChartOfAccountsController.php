<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountsController extends Controller
{
    public function __construct(protected FinancialReportService $financialReports) {}

    public function index(): View
    {
        abort_unless(request()->user()?->can('finance.reports.view'), 403);

        return view('admin.finance.chart-of-accounts-index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('finance.reports.view'), 403);

        $accounts = Account::query()->orderBy('account_code')->get();

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('finance.voucher.create'), 403);
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:20', 'unique:accounts,account_code'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        Account::query()->create(array_merge($data, ['is_system' => false]));

        return response()->json(['status' => true, 'message' => 'Account created.'], 201);
    }
}
