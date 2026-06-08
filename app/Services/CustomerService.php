<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Customer master: codes, block/reactivate, soft delete.
 */
class CustomerService
{
    /**
     * Create a new customer (active immediately for sales use).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data, User $creator): Customer
    {
        return DB::transaction(function () use ($data, $creator): Customer {
            $data['customer_code'] = $this->nextCustomerCode();
            $data['status'] = CustomerStatus::Active;
            $data['created_by'] = $creator->id;

            return Customer::query()->create($data);
        });
    }

    /**
     * Update customer master fields (not status — use block/activate).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            unset($data['customer_code'], $data['status'], $data['created_by']);
            $customer->update($data);

            return $customer->fresh();
        });
    }

    /**
     * Block an active customer.
     *
     * @throws Throwable
     */
    public function block(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer): Customer {
            $customer->update([
                'status' => CustomerStatus::Blocked,
            ]);

            return $customer->fresh();
        });
    }

    /**
     * Reactivate a blocked customer.
     *
     * @throws Throwable
     */
    public function activate(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer): Customer {
            $customer->update([
                'status' => CustomerStatus::Active,
            ]);

            return $customer->fresh();
        });
    }

    /**
     * Soft-delete customer when allowed.
     *
     * @throws Throwable
     */
    public function delete(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $customer->delete();
        });
    }

    /**
     * Generate next internal customer code (C-00001 style).
     */
    protected function nextCustomerCode(): string
    {
        $max = (int) Customer::withTrashed()->max('id');

        return 'C-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
