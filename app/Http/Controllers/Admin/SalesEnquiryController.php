<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesEnquiry;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesEnquiryController extends Controller
{
    use IssuesDocumentNumbers;

    public function index(): View
    {
        abort_unless(request()->user()?->can('sales.quotation.create'), 403);

        return view('admin.sales.enquiries-index', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('sales.quotation.create'), 403);

        $query = SalesEnquiry::query()
            ->select(['id', 'enquiry_number', 'contact_name', 'phone', 'status', 'created_at'])
            ->with(['customer:id,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            fn ($q, string $term) => $q->where('enquiry_number', 'like', '%'.$term.'%')
                ->orWhere('contact_name', 'like', '%'.$term.'%'),
            ['id', 'enquiry_number', 'status', 'created_at'],
        );

        $data = $payload['rows']->map(fn (SalesEnquiry $row) => [
            'enquiry_number' => $row->enquiry_number,
            'customer' => $row->customer?->name ?? '—',
            'contact_name' => $row->contact_name,
            'phone' => $row->phone,
            'status' => $row->status,
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('sales.quotation.create'), 403);
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:191'],
            'notes' => ['nullable', 'string'],
        ]);

        SalesEnquiry::query()->create([
            'enquiry_number' => $this->nextDocumentCode('sales_enquiries', 'ENQ-'),
            'customer_id' => $data['customer_id'] ?? null,
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'open',
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Sales enquiry recorded.'], 201);
    }
}
