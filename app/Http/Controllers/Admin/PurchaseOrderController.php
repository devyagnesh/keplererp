<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Mail\PurchaseOrderSentMail;
use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\GstCalculationService;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\PoApprovalService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Support\ErpDataTable;
use App\Support\PdfDownloadLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class PurchaseOrderController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected GstCalculationService $gst,
        protected WhatsAppNotificationService $whatsappNotifications,
        protected PdfGeneratorService $pdfGenerator,
        protected PoApprovalService $poApprovals
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return view('admin.purchase.orders-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::query()
            ->select(['id', 'po_number', 'vendor_id', 'order_date', 'status', 'total_amount', 'created_at'])
            ->with(['vendor:id,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('po_number', 'like', '%'.$term.'%')
                    ->orWhereHas('vendor', function ($vq) use ($term): void {
                        $vq->where('name', 'like', '%'.$term.'%');
                    });
            },
            ['id', 'po_number', 'order_date', 'status', 'total_amount', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (PurchaseOrder $row) use ($actor) {
            return [
                'po_number' => $row->po_number,
                'vendor' => $row->vendor?->name ?? '—',
                'order_date' => $row->order_date?->format('Y-m-d'),
                'status' => $row->status,
                'total_amount' => (string) $row->total_amount,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($row, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    protected function buildActionHtml(PurchaseOrder $po, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('approve', $po)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-po-approve" data-confirm="Approve this purchase order?" data-url="'
                .e(route('admin.purchase.orders.approve', $po)).'">Approve</button>';
        }
        if ($actor->can('financeApprove', $po)) {
            $html .= '<button type="button" class="btn btn-sm btn-warning btn-wave js-po-finance-approve" data-confirm="Finance-approve this PO?" data-url="'
                .e(route('admin.purchase.orders.finance-approve', $po)).'">Finance approve</button>';
        }
        if ($actor->can('reject', $po)) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-wave js-po-reject" data-url="'
                .e(route('admin.purchase.orders.reject', $po)).'">Reject</button>';
        }
        if ($actor->can('markSent', $po)) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-primary btn-wave js-po-mark-sent" data-confirm="Mark this PO as sent to the vendor?" data-url="'
                .e(route('admin.purchase.orders.mark-sent', $po)).'">Mark sent</button>';
        }
        if ($actor->can('view', $po) && in_array($po->status, ['approved', 'sent'], true)) {
            $html .= PdfDownloadLink::button(route('admin.purchase.orders.pdf', $po));
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Download or generate purchase order PDF.
     */
    public function downloadPdf(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);
        if (! in_array($purchaseOrder->status, ['approved', 'sent'], true)) {
            abort(404);
        }

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::PurchaseOrder,
            $purchaseOrder,
            $request->user()?->id
        );
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('admin.purchase.orders-create', [
            'vendors' => Vendor::query()->orderBy('name')->get(['id', 'name', 'vendor_code']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'approvedRequisitions' => PurchaseRequisition::query()
                ->where('status', 'approved')
                ->orderByDesc('id')
                ->get(['id', 'pr_number']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($request, $user): void {
                $v = $request->validated();
                $vendor = Vendor::query()->findOrFail((int) $v['vendor_id']);
                $company = Company::query()->orderBy('id')->firstOrFail();
                $pr = null;
                if (! empty($v['pr_id'])) {
                    $pr = PurchaseRequisition::query()->findOrFail((int) $v['pr_id']);
                    if ($pr->status !== 'approved') {
                        throw new InvalidArgumentException('Purchase requisition must be approved before creating a PO.');
                    }
                }
                $computed = $this->computeLinesAndHeader($v, $vendor, $company);
                $warehouseId = $v['warehouse_id'] ?? $pr?->warehouse_id;
                $po = PurchaseOrder::query()->create([
                    'po_number' => $this->nextDocumentCode('purchase_orders', 'PO-'),
                    'pr_id' => $pr?->id,
                    'vendor_id' => $vendor->id,
                    'warehouse_id' => $warehouseId,
                    'order_date' => $v['order_date'],
                    'expected_delivery' => $v['expected_delivery'] ?? null,
                    'payment_terms_days' => (int) ($v['payment_terms_days'] ?? 30),
                    'status' => 'draft',
                    'created_by' => $user->id,
                    'notes' => $v['notes'] ?? null,
                    'subtotal' => $computed['subtotal'],
                    'discount_amount' => '0.00',
                    'taxable_amount' => $computed['taxable_amount'],
                    'cgst_amount' => $computed['cgst_amount'],
                    'sgst_amount' => $computed['sgst_amount'],
                    'igst_amount' => $computed['igst_amount'],
                    'total_amount' => $computed['total_amount'],
                ]);
                foreach ($computed['lines'] as $line) {
                    $po->lines()->create($line);
                }
                if ($pr !== null) {
                    $pr->update(['status' => 'converted']);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Purchase order created successfully.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseOrderController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create purchase order.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{lines: list<array<string, mixed>>, subtotal: string, taxable_amount: string, cgst_amount: string, sgst_amount: string, igst_amount: string, total_amount: string}
     */
    protected function computeLinesAndHeader(array $validated, Vendor $vendor, Company $company): array
    {
        $subtotal = '0.00';
        $cgstTot = '0.00';
        $sgstTot = '0.00';
        $igstTot = '0.00';
        $total = '0.00';
        $linesOut = [];

        foreach ($validated['lines'] as $line) {
            $item = Item::query()->findOrFail((int) $line['item_id']);
            $qty = (string) $line['quantity'];
            $unitCost = (string) $line['unit_cost'];
            $taxable = $this->gst->lineTaxable($qty, $unitCost);
            $split = $this->gst->splitLineTax($taxable, (string) $item->gst_rate, $company->state_code, $vendor->state_code);
            $lineTotal = bcadd(bcadd(bcadd($split['taxable'], $split['cgst'], 2), $split['sgst'], 2), $split['igst'], 2);
            $subtotal = bcadd($subtotal, $split['taxable'], 2);
            $cgstTot = bcadd($cgstTot, $split['cgst'], 2);
            $sgstTot = bcadd($sgstTot, $split['sgst'], 2);
            $igstTot = bcadd($igstTot, $split['igst'], 2);
            $total = bcadd($total, $lineTotal, 2);
            $linesOut[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'gst_rate' => (string) $item->gst_rate,
                'taxable_value' => $split['taxable'],
                'cgst' => $split['cgst'],
                'sgst' => $split['sgst'],
                'igst' => $split['igst'],
                'line_total' => $lineTotal,
            ];
        }

        return [
            'lines' => $linesOut,
            'subtotal' => $subtotal,
            'taxable_amount' => $subtotal,
            'cgst_amount' => $cgstTot,
            'sgst_amount' => $sgstTot,
            'igst_amount' => $igstTot,
            'total_amount' => $total,
        ];
    }

    /**
     * SRS §19.1: PO cannot be approved by the same user who created it (four-eyes).
     *
     * @throws InvalidArgumentException
     */
    protected function assertApproverNotSameAsCreator(Request $request, PurchaseOrder $purchaseOrder): void
    {
        $actorId = $request->user()?->id;
        if ($actorId === null || $purchaseOrder->created_by === null) {
            return;
        }
        if ((int) $actorId === (int) $purchaseOrder->created_by) {
            throw new InvalidArgumentException(
                'You cannot approve a purchase order you created. Another authorised user must approve (four-eyes policy).'
            );
        }
    }

    /**
     * SRS §19.1: header total must match sum of line totals before approval.
     *
     * @throws InvalidArgumentException
     */
    protected function assertPoHeaderMatchesLineTotals(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('lines');
        $sum = '0.00';
        foreach ($purchaseOrder->lines as $line) {
            $sum = bcadd($sum, (string) $line->line_total, 2);
        }
        if (bccomp((string) $purchaseOrder->total_amount, $sum, 2) !== 0) {
            throw new InvalidArgumentException('PO total does not match line totals; approval blocked.');
        }
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('approve', $purchaseOrder);

        try {
            $this->assertApproverNotSameAsCreator($request, $purchaseOrder);
            $this->assertPoHeaderMatchesLineTotals($purchaseOrder);

            $company = Company::query()->orderBy('id')->first();
            $threshold = $company?->po_approval_threshold;
            $approver = $request->user();
            if ($approver === null) {
                abort(403);
            }

            if ($threshold !== null && bccomp((string) $purchaseOrder->total_amount, (string) $threshold, 2) > 0) {
                $purchaseOrder->update(['status' => 'pending_finance']);
                $message = 'Purchase order pending finance approval (above threshold).';
                $purchaseOrder->refresh();
                $this->poApprovals->record($purchaseOrder, $approver, 'pending_finance', 1);
                $this->whatsappNotifications->notifyPurchaseOrderCreator($purchaseOrder, 'pending_finance');
            } else {
                $purchaseOrder->update(['status' => 'approved']);
                $message = 'Purchase order approved.';
                $purchaseOrder->refresh();
                $this->poApprovals->record($purchaseOrder, $approver, 'approved', 1);
                $this->whatsappNotifications->notifyPurchaseOrderApproved($purchaseOrder);
                $this->whatsappNotifications->notifyPurchaseOrderCreator($purchaseOrder, 'final_approved');
                $this->pdfGenerator->queue(PdfDocumentType::PurchaseOrder, $purchaseOrder, $approver->id);
            }

            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseOrderController@approve failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not approve purchase order.',
            ], 500);
        }
    }

    public function financeApprove(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('financeApprove', $purchaseOrder);

        try {
            $user = $request->user();
            if ($user === null) {
                abort(403);
            }
            $this->assertApproverNotSameAsCreator($request, $purchaseOrder);
            $this->assertPoHeaderMatchesLineTotals($purchaseOrder);

            $purchaseOrder->update([
                'status' => 'approved',
                'finance_approved_at' => now(),
                'finance_approved_by' => $user->id,
            ]);
            $purchaseOrder->refresh();
            $this->poApprovals->record($purchaseOrder, $user, 'finance_approved', 2);
            $this->whatsappNotifications->notifyPurchaseOrderApproved($purchaseOrder);
            $this->whatsappNotifications->notifyPurchaseOrderCreator($purchaseOrder, 'final_approved');
            $this->pdfGenerator->queue(PdfDocumentType::PurchaseOrder, $purchaseOrder, $user->id);

            return response()->json([
                'status' => true,
                'message' => 'Finance approval recorded; purchase order is approved.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseOrderController@financeApprove failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not finance-approve purchase order.',
            ], 500);
        }
    }

    public function reject(RejectPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('reject', $purchaseOrder);

        try {
            $this->assertApproverNotSameAsCreator($request, $purchaseOrder);

            $data = $request->validated();
            $purchaseOrder->update([
                'status' => 'rejected',
                'rejected_reason' => $data['rejected_reason'],
            ]);
            $purchaseOrder->refresh();
            $this->whatsappNotifications->notifyPurchaseOrderCreator($purchaseOrder, 'rejected', $data['rejected_reason']);

            return response()->json([
                'status' => true,
                'message' => 'Purchase order rejected.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseOrderController@reject failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not reject purchase order.',
            ], 500);
        }
    }

    public function markSent(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('markSent', $purchaseOrder);

        try {
            $purchaseOrder->update(['status' => 'sent']);
            $purchaseOrder->loadMissing('vendor');
            $purchaseOrder->refresh();
            $this->whatsappNotifications->notifyPurchaseOrderDispatchedToVendor($purchaseOrder);
            $document = $this->pdfGenerator->generate(PdfDocumentType::PurchaseOrder, $purchaseOrder, $request->user()?->id);
            $vendorEmail = $purchaseOrder->vendor?->email;
            if ($vendorEmail !== null && $vendorEmail !== '') {
                Mail::to($vendorEmail)->queue(
                    new PurchaseOrderSentMail($purchaseOrder, $document)
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Purchase order marked as sent to vendor.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseOrderController@markSent failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update purchase order.',
            ], 500);
        }
    }
}
