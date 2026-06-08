<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Vendor;
use App\Enums\PdfDocumentType;
use App\Services\GstrExportService;
use App\Services\FinancialReportService;
use App\Services\GstPeriodLockService;
use App\Services\Pdf\PdfGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only operational snapshot for authorised roles.
 */
class ReportsController extends Controller
{
    public function __construct(
        protected GstrExportService $gstrExport,
        protected PdfGeneratorService $pdfGenerator,
        protected GstPeriodLockService $gstPeriodLock,
        protected FinancialReportService $financialReports
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if (! $user->can('reports.sales')
            && ! $user->can('reports.purchase')
            && ! $user->can('reports.inventory')
            && ! $user->can('reports.finance')
            && ! $user->can('hr.employee.manage')) {
            abort(403);
        }

        $stats = [
            'vendors' => $user->can('reports.purchase') ? Vendor::query()->count() : null,
            'customers' => $user->can('reports.sales') ? Customer::query()->count() : null,
            'items' => $user->can('reports.inventory') ? Item::query()->count() : null,
            'open_purchase_orders' => $user->can('reports.purchase')
                ? PurchaseOrder::query()->where('status', 'draft')->count()
                : null,
            'open_sales_orders' => $user->can('reports.sales')
                ? SalesOrder::query()->where('dispatched_at', null)->count()
                : null,
            'employees' => $user->can('hr.employee.manage') ? Employee::query()->count() : null,
        ];

        return view('admin.reports.index', [
            'stats' => $stats,
            'items' => $user->can('reports.inventory')
                ? Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name'])
                : collect(),
            'vendors' => $user->can('finance.reports.view')
                ? Vendor::query()->orderBy('name')->get(['id', 'name', 'vendor_code'])
                : collect(),
        ]);
    }

    public function exportGstr1(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $year = (int) $request->input('year', (int) now()->year);
        $month = (int) $request->input('month', (int) now()->month);

        return $this->gstrExport->downloadGstr1Csv($year, $month);
    }

    public function exportGstr1Json(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $year = (int) $request->input('year', (int) now()->year);
        $month = (int) $request->input('month', (int) now()->month);

        return $this->gstrExport->downloadGstr1Json($year, $month);
    }

    public function lockGstPeriod(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $this->gstPeriodLock->lock((int) $data['year'], (int) $data['month'], $user);

            return response()->json([
                'status' => true,
                'message' => 'GST period locked successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function exportGstr3b(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $year = (int) $request->input('year', (int) now()->year);
        $month = (int) $request->input('month', (int) now()->month);

        return $this->gstrExport->downloadGstr3bSummaryCsv($year, $month);
    }

    public function exportGstr1Pdf(Request $request): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $year = (int) $request->input('year', (int) now()->year);
        $month = (int) $request->input('month', (int) now()->month);
        $company = Company::query()->orderBy('id')->firstOrFail();

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Gstr1,
            $company,
            $user->id,
            ['year' => $year, 'month' => $month]
        );
    }

    public function exportGstr3bPdf(Request $request): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $year = (int) $request->input('year', (int) now()->year);
        $month = (int) $request->input('month', (int) now()->month);
        $company = Company::query()->orderBy('id')->firstOrFail();

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Gstr3b,
            $company,
            $user->id,
            ['year' => $year, 'month' => $month]
        );
    }

    public function exportStockLedgerPdf(Request $request): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->can('reports.inventory')) {
            abort(403);
        }

        $item = Item::query()->findOrFail((int) $request->input('item_id'));
        $meta = [
            'item_id' => $item->id,
            'warehouse_id' => $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            'date_from' => (string) $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => (string) $request->input('date_to', now()->toDateString()),
        ];

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::StockLedger,
            $item,
            $user->id,
            $meta
        );
    }

    public function exportVendorStatementPdf(Request $request, Vendor $vendor): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $meta = [
            'date_from' => (string) $request->input('date_from', now()->startOfYear()->toDateString()),
            'date_to' => (string) $request->input('date_to', now()->toDateString()),
        ];

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::VendorStatement,
            $vendor,
            $user->id,
            $meta
        );
    }

    public function exportProductionOrderPdf(Request $request, ProductionOrder $productionOrder): Response
    {
        $this->authorize('view', $productionOrder);

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::ProductionOrder,
            $productionOrder,
            $request->user()?->id
        );
    }

    public function exportProfitLoss(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $from = Carbon::parse($request->input('date_from', now()->startOfYear()->toDateString()));
        $to = Carbon::parse($request->input('date_to', now()->toDateString()));

        return $this->financialReports->downloadProfitAndLossCsv($from, $to);
    }

    public function exportBalanceSheet(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        return $this->financialReports->downloadBalanceSheetCsv($asOf);
    }

    public function chartOfAccounts(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $accounts = $this->financialReports->chartOfAccounts();

        return response()->json([
            'status' => true,
            'data' => $accounts,
        ]);
    }
}
