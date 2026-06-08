<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreditNoteRequest;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Services\CreditNoteService;
use App\Support\ErpDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class CreditNoteController extends Controller
{
    public function __construct(protected CreditNoteService $creditNotes) {}

    public function index(): View
    {
        $this->authorize('viewAny', CreditNote::class);

        return view('admin.sales.credit-notes-index', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'items' => \App\Models\Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CreditNote::class);

        $query = CreditNote::query()
            ->select(['id', 'credit_note_number', 'customer_id', 'credit_note_date', 'total_amount', 'status', 'created_at'])
            ->with(['customer:id,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            fn ($q, string $term) => $q->where('credit_note_number', 'like', '%'.$term.'%'),
            ['id', 'credit_note_number', 'credit_note_date', 'total_amount', 'created_at'],
        );

        $data = $payload['rows']->map(fn (CreditNote $row) => [
            'credit_note_number' => $row->credit_note_number,
            'customer' => $row->customer?->name ?? '—',
            'credit_note_date' => $row->credit_note_date?->format('Y-m-d'),
            'total_amount' => (string) $row->total_amount,
            'status' => $row->status,
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function store(StoreCreditNoteRequest $request): JsonResponse
    {
        $this->authorize('create', CreditNote::class);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $customer = Customer::query()->findOrFail((int) $v['customer_id']);
            $date = isset($v['credit_note_date']) ? Carbon::parse($v['credit_note_date']) : null;
            $note = $this->creditNotes->createPosted(
                $customer,
                $v['lines'],
                isset($v['invoice_id']) ? (int) $v['invoice_id'] : null,
                $v['reason'] ?? null,
                $user,
                $date
            );

            return response()->json([
                'status' => true,
                'message' => 'Credit note '.$note->credit_note_number.' posted successfully.',
                'data' => ['id' => $note->id],
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('CreditNoteController@store failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not post credit note.'], 500);
        }
    }
}
