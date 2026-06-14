<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Sales invoice listing and PDF download.
 */
class InvoiceController extends Controller
{
    public function __construct(
        protected InvoicePdfService $pdf
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('admin.sales.invoices-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->select([
                'id',
                'invoice_number',
                'customer_id',
                'sales_order_id',
                'invoice_date',
                'due_date',
                'total_amount',
                'amount_paid',
                'status',
                'created_at',
            ])
            ->with([
                'customer:id,name',
                'salesOrder:id,order_number',
            ]);

        $status = $request->input('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('invoice_number', 'like', '%'.$term.'%')
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', '%'.$term.'%'));
            },
            ['id', 'invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status', 'created_at'],
        );

        $data = $payload['rows']->map(fn (Invoice $row) => [
            'invoice_number' => $row->invoice_number,
            'customer' => $row->customer?->name ?? '—',
            'sales_order' => $row->salesOrder?->order_number ?? '—',
            'invoice_date' => $row->invoice_date?->format('Y-m-d'),
            'due_date' => $row->due_date?->format('Y-m-d'),
            'total_amount' => (string) $row->total_amount,
            'status' => $row->status,
            'action' => $row->status === 'posted'
                ? '<a href="'.e(route('admin.sales.invoices.pdf', $row)).'" class="btn btn-sm btn-outline-secondary btn-wave" target="_blank" rel="noopener">PDF</a>'
                : '—',
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        if ($invoice->status !== 'posted') {
            abort(404);
        }

        return $this->pdf->download($invoice);
    }
}
