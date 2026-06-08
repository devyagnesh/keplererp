<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Customer master — list, CRUD, block / reactivate.
 */
class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepositoryInterface $customers,
        protected CustomerService $customerService
    ) {}

    /**
     * Customers listing (DataTables server-side).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('admin.customers.index');
    }

    /**
     * DataTables JSON for customers.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $payload = $this->customers->getDataTableRows($request);
        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (Customer $customer) use ($actor) {
            return [
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'gstin' => $customer->gstin ?? '—',
                'city' => $customer->city,
                'status' => $customer->status->label(),
                'created_at' => $customer->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($customer, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    /**
     * Build action buttons for the current row.
     */
    protected function buildActionHtml(Customer $customer, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';

        if ($actor->can('update', $customer)) {
            $html .= '<a href="'.e(route('admin.customers.edit', $customer)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }

        if ($actor->can('delete', $customer)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-customer-delete" data-url="'
                .e(route('admin.customers.destroy', $customer)).'">Delete</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Show create customer form.
     */
    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('admin.customers.create', [
            'gstStates' => config('gst.state_codes', []),
            'priceLists' => \App\Models\PriceList::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a new customer (active).
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $actor = $request->user();
            if ($actor === null) {
                abort(403);
            }

            $validated = $request->validated();
            $customer = $this->customerService->create($validated, $actor);

            return response()->json([
                'status' => true,
                'message' => 'Customer created successfully.',
                'data' => ['id' => $customer->id],
            ], 201);
        } catch (Throwable $e) {
            Log::error('CustomerController@store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create customer.',
            ], 500);
        }
    }

    /**
     * Show edit customer form.
     */
    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('admin.customers.edit', [
            'customer' => $customer->load('addresses'),
            'gstStates' => config('gst.state_codes', []),
            'priceLists' => \App\Models\PriceList::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Update customer master fields.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->customerService->update($customer, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Customer updated successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('CustomerController@update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update customer.',
            ], 500);
        }
    }

    /**
     * Block an active customer.
     */
    public function block(Customer $customer): JsonResponse
    {
        $this->authorize('block', $customer);

        try {
            $this->customerService->block($customer);

            return response()->json([
                'status' => true,
                'message' => 'Customer blocked.',
            ]);
        } catch (Throwable $e) {
            Log::error('CustomerController@block failed', [
                'message' => $e->getMessage(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not block customer.',
            ], 500);
        }
    }

    /**
     * Reactivate a blocked customer.
     */
    public function activate(Customer $customer): JsonResponse
    {
        $this->authorize('activate', $customer);

        try {
            $this->customerService->activate($customer);

            return response()->json([
                'status' => true,
                'message' => 'Customer reactivated.',
            ]);
        } catch (Throwable $e) {
            Log::error('CustomerController@activate failed', [
                'message' => $e->getMessage(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not reactivate customer.',
            ], 500);
        }
    }

    /**
     * Soft-delete customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        try {
            $this->customerService->delete($customer);

            return response()->json([
                'status' => true,
                'message' => 'Customer deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('CustomerController@destroy failed', [
                'message' => $e->getMessage(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete customer.',
            ], 500);
        }
    }
}
