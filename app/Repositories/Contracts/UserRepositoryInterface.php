<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Data access for {@see User} (listing, lookup).
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by primary key or fail.
     */
    public function findById(int $id): User;

    /**
     * Paginated list for simple admin tables (non-DataTables).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Server-side DataTables payload: total counts plus matching users (with roles).
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, rows: Collection<int, User>}
     */
    public function getDataTableRows(Request $request): array;
}
