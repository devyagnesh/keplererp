<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertSalesQuotationRequest;
use App\Http\Requests\StoreSalesQuotationRequest;
use App\Mail\SalesQuotationSentMail;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\PriceListService;
use App\Services\SalesQuotationConversionService;
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
use Throwable;

class SalesQuotationController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected SalesQuotationConversionService $conversion,
        protected PdfGeneratorService $pdfGenerator,
        protected PriceListService $priceLists,
        protected WhatsAppNotificationService $whatsappNotifications
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SalesQuotation::class);

        return view('admin.sales.quotations-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesQuotation::class);

        $query = SalesQuotation::query()
            ->select(['sales_quotations.id', 'quote_number', 'customer_id', 'quote_date', 'status', 'created_at'])
            ->with(['customer:id,name,customer_code']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('quote_number', 'like', '%'.$term.'%')
                    ->orWhereHas('customer', function ($cq) use ($term): void {
                        $cq->where('name', 'like', '%'.$term.'%');
                    });
            },
            ['id', 'quote_number', 'quote_date', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (SalesQuotation $row) use ($actor) {
            return [
                'quote_number' => $row->quote_number,
                'customer' => $row->customer?->name ?? '—',
                'quote_date' => $row->quote_date?->format('Y-m-d'),
                'status' => $row->status,
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

    protected function buildActionHtml(SalesQuotation $quotation, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('send', $quotation)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-qt-send" data-confirm="Send quotation to customer?" data-url="'
                .e(route('admin.sales.quotations.send', $quotation)).'">Send</button>';
        }
        if ($actor->can('convert', $quotation)) {
            $html .= '<button type="button" class="btn btn-sm btn-primary btn-wave js-qt-convert" data-url="'
                .e(route('admin.sales.quotations.convert', $quotation)).'">Convert to SO</button>';
        }
        if ($actor->can('view', $quotation)) {
            $html .= PdfDownloadLink::button(route('admin.sales.quotations.pdf', $quotation), 'PDF');
        }
        $html .= '</div>';

        return $html;
    }

    public function convert(ConvertSalesQuotationRequest $request, SalesQuotation $salesQuotation): JsonResponse
    {
        $this->authorize('convert', $salesQuotation);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $so = $this->conversion->convert(
                $salesQuotation,
                $user,
                (int) $request->validated('warehouse_id'),
                $this->nextDocumentCode('sales_orders', 'SO-'),
                $request->boolean('credit_limit_override')
            );

            return response()->json([
                'status' => true,
                'message' => 'Sales order '.$so->order_number.' created from quotation.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('SalesQuotationController@convert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not convert quotation.',
            ], 500);
        }
    }

    public function create(): View
    {
        $this->authorize('create', SalesQuotation::class);

        return view('admin.sales.quotations-create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'customer_code']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreSalesQuotationRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $quotation = null;
            DB::transaction(function () use ($request, $user, &$quotation): void {
                $v = $request->validated();
                $quotation = SalesQuotation::query()->create([
                    'quote_number' => $this->nextDocumentCode('sales_quotations', 'QT-'),
                    'customer_id' => $v['customer_id'],
                    'quote_date' => $v['quote_date'],
                    'valid_until' => $v['valid_until'] ?? null,
                    'status' => 'draft',
                    'created_by' => $user->id,
                    'notes' => $v['notes'] ?? null,
                ]);
                $customer = Customer::query()->findOrFail((int) $v['customer_id']);
                foreach ($v['lines'] as $line) {
                    $item = Item::query()->findOrFail((int) $line['item_id']);
                    $unitPrice = isset($line['unit_price']) && $line['unit_price'] !== ''
                        ? (string) $line['unit_price']
                        : $this->priceLists->unitPriceForCustomer($customer, $item, '0');
                    $quotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $unitPrice,
                    ]);
                }
            });

            if ($quotation instanceof SalesQuotation) {
                $this->pdfGenerator->queue(PdfDocumentType::Quotation, $quotation, $user->id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Quotation created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('SalesQuotationController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create quotation.',
            ], 500);
        }
    }

    public function send(Request $request, SalesQuotation $salesQuotation): JsonResponse
    {
        $this->authorize('send', $salesQuotation);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $salesQuotation->update(['status' => 'sent']);
            $salesQuotation->loadMissing('customer');
            $doc = $this->pdfGenerator->generate(PdfDocumentType::Quotation, $salesQuotation, $user->id);
            $pdfUrl = $this->pdfGenerator->signedDownloadUrl($doc);
            $customer = $salesQuotation->customer;
            if ($customer !== null) {
                $this->whatsappNotifications->notifyQuotationSent($salesQuotation, $pdfUrl);
                if ($customer->email !== null && $customer->email !== '') {
                    Mail::to($customer->email)->queue(
                        new SalesQuotationSentMail($salesQuotation, $doc)
                    );
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Quotation sent. PDF generated.',
                'data' => ['pdf_url' => $pdfUrl],
            ]);
        } catch (Throwable $e) {
            Log::error('SalesQuotationController@send failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not send quotation.'], 500);
        }
    }

    /**
     * Download or generate quotation PDF.
     */
    public function downloadPdf(Request $request, SalesQuotation $salesQuotation): Response
    {
        $this->authorize('view', $salesQuotation);

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Quotation,
            $salesQuotation,
            $request->user()?->id
        );
    }
}
