<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of {@see UserRepositoryInterface}.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): User
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'whatsapp_number',
                'employee_id',
                'warehouse_id',
                'is_active',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->with(['roles:id,name'])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'email', 'is_active', 'created_at'])
            ->with(['roles:id,name'])
            ->latest('id')
            ->paginate($perPage);
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

        $query = User::query()
            ->select([
                'id',
                'name',
                'email',
                'is_active',
                'created_at',
            ])
            ->with(['roles:id,name']);

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $recordsTotal = User::query()->count();
        $recordsFiltered = (clone $query)->count();

        $orderable = ['id', 'name', 'email', 'created_at', 'is_active'];
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

        /** @var Collection<int, User> $rows */
        $rows = $query->skip($start)->take($length)->get();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $rows,
        ];
    }
}
