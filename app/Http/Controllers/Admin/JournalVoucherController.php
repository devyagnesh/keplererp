<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalVoucherRequest;
use App\Models\JournalVoucher;
use App\Models\User;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class JournalVoucherController extends Controller
{
    use IssuesDocumentNumbers;

    public function index(): View
    {
        $this->authorize('viewAny', JournalVoucher::class);

        return view('admin.finance.vouchers-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JournalVoucher::class);

        $query = JournalVoucher::query()->select(['id', 'voucher_number', 'voucher_date', 'status', 'created_at']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('voucher_number', 'like', '%'.$term.'%')
                    ->orWhere('narration', 'like', '%'.$term.'%');
            },
            ['id', 'voucher_number', 'voucher_date', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (JournalVoucher $row) use ($actor) {
            return [
                'voucher_number' => $row->voucher_number,
                'voucher_date' => $row->voucher_date?->format('Y-m-d'),
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

    protected function buildActionHtml(JournalVoucher $voucher, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('post', $voucher)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-jv-post" data-url="'
                .e(route('admin.finance.vouchers.post', $voucher)).'">Post</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', JournalVoucher::class);

        return view('admin.finance.vouchers-create');
    }

    public function store(StoreJournalVoucherRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($request, $user): void {
                $v = $request->validated();
                $jv = JournalVoucher::query()->create([
                    'voucher_number' => $this->nextDocumentCode('journal_vouchers', 'JV-'),
                    'voucher_date' => $v['voucher_date'],
                    'narration' => $v['narration'] ?? null,
                    'status' => 'draft',
                    'created_by' => $user->id,
                ]);
                foreach ($v['lines'] as $line) {
                    $jv->lines()->create([
                        'account_code' => $line['account_code'],
                        'account_name' => $line['account_name'] ?? null,
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                    ]);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Journal voucher saved as draft.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('JournalVoucherController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not save voucher.',
            ], 500);
        }
    }

    public function post(JournalVoucher $journalVoucher): JsonResponse
    {
        $this->authorize('post', $journalVoucher);

        try {
            $journalVoucher->update(['status' => 'posted']);

            return response()->json([
                'status' => true,
                'message' => 'Voucher posted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('JournalVoucherController@post failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not post voucher.',
            ], 500);
        }
    }
}
