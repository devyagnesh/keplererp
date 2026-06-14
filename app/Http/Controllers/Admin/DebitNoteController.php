<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Read-only listing of vendor debit notes (auto-created on GRN returns).
 */
class DebitNoteController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DebitNote::class);

        return view('admin.purchase.debit-notes-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DebitNote::class);

        $query = DebitNote::query()
            ->select([
                'id',
                'debit_note_number',
                'vendor_id',
                'grn_return_id',
                'amount',
                'status',
                'reason',
                'created_at',
            ])
            ->with([
                'vendor:id,name',
                'grnReturn:id,return_number',
            ]);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('debit_note_number', 'like', '%'.$term.'%')
                    ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', '%'.$term.'%'));
            },
            ['id', 'debit_note_number', 'amount', 'status', 'created_at'],
        );

        $data = $payload['rows']->map(fn (DebitNote $row) => [
            'debit_note_number' => $row->debit_note_number,
            'vendor' => $row->vendor?->name ?? '—',
            'grn_return' => $row->grnReturn?->return_number ?? '—',
            'amount' => (string) $row->amount,
            'status' => $row->status,
            'reason' => $row->reason !== null && $row->reason !== ''
                ? Str::limit($row->reason, 60)
                : '—',
            'created_at' => $row->created_at?->format('Y-m-d H:i'),
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }
}
