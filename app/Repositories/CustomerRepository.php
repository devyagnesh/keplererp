<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Eloquent implementation of {@see CustomerRepositoryInterface}.
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): Customer
    {
        return Customer::query()
            ->select([
                'id',
                'customer_code',
                'name',
                'contact_person',
                'email',
                'phone',
                'gstin',
                'pan',
                'address_line1',
                'address_line2',
                'city',
                'state_code',
                'pincode',
                'payment_terms',
                'notes',
                'status',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTableRows(Request $request): array
    {
        $draw = (int) $request->input('draw', 1);
        $search = $request->input('search.value');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        $query = Customer::query()
            ->select([
                'id',
                'customer_code',
                'name',
                'phone',
                'gstin',
                'city',
                'status',
                'created_at',
            ]);

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('customer_code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('gstin', 'like', '%'.$search.'%');
            });
        }

        $recordsTotal = Customer::query()->count();
        $recordsFiltered = (clone $query)->count();

        $orderable = ['id', 'customer_code', 'name', 'phone', 'gstin', 'city', 'status', 'created_at'];
        $columns = $request->input('columns', []);
        $orders = $request->input('order', []);
        if (isset($orders[0]['column'])) {
            $colIdx = (int) $orders[0]['column'];
            $dir = ($orders[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $colData = $columns[$colIdx]['data'] ?? 'id';
            if (in_array($colData, $orderable, true)) {
                $query->orderBy($colData, $dir);
            } else {
                $query->orderByDesc('id');
            }
        } else {
            $query->orderByDesc('id');
        }

        if ($length === -1) {
            $length = max(1, $recordsFiltered);
        }

        /** @var Collection<int, Customer> $rows */
        $rows = $query->skip($start)->take($length)->get();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $rows,
        ];
    }
}
