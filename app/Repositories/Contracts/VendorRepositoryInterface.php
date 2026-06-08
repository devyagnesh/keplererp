<?php

namespace App\Repositories\Contracts;

use App\Models\Vendor;
use Illuminate\Http\Request;

/**
 * Data access for vendor (supplier) master records.
 */
interface VendorRepositoryInterface
{
    /**
     * Find a vendor by primary key or fail.
     */
    public function findById(int $id): Vendor;

    /**
     * Server-side DataTables payload.
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, rows: \Illuminate\Database\Eloquent\Collection<int, Vendor>}
     */
    public function getDataTableRows(Request $request): array;
}
