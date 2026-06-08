<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Data access for customer master records.
 */
interface CustomerRepositoryInterface
{
    /**
     * Find a customer by primary key or fail.
     */
    public function findById(int $id): Customer;

    /**
     * Server-side DataTables payload.
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, rows: \Illuminate\Database\Eloquent\Collection<int, Customer>}
     */
    public function getDataTableRows(Request $request): array;
}
