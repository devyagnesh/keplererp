<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * User lifecycle: passwords, roles, safety rules for last Super Admin.
 */
class UserService
{
    /**
     * Create a user and assign roles.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     *
     * @throws Throwable
     */
    public function create(array $data, array $roleIds): User
    {
        $roleIds = $this->normalizeRoleIds($roleIds);

        return DB::transaction(function () use ($data, $roleIds): User {
            $data['password'] = Hash::make((string) $data['password']);
            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            $user = User::query()->create($data);
            $user->syncRoles(Role::query()->whereIn('id', $roleIds)->get());

            return $user->fresh(['roles']);
        });
    }

    /**
     * Update a user and sync roles.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     *
     * @throws Throwable
     */
    public function update(User $user, array $data, array $roleIds): User
    {
        $roleIds = $this->normalizeRoleIds($roleIds);

        return DB::transaction(function () use ($user, $data, $roleIds): User {
            $this->assertCanChangeRoles($user, $roleIds);

            if (! empty($data['password'])) {
                $data['password'] = Hash::make((string) $data['password']);
            } else {
                unset($data['password']);
            }

            $data['is_active'] = (bool) ($data['is_active'] ?? $user->is_active);

            $user->update($data);
            $user->syncRoles(Role::query()->whereIn('id', $roleIds)->get());

            return $user->fresh(['roles']);
        });
    }

    /**
     * Soft-delete a user when allowed.
     *
     * @throws ValidationException
     */
    public function delete(User $subject, User $actor): void
    {
        if ($subject->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        if ($this->isLastSuperAdmin($subject)) {
            throw ValidationException::withMessages([
                'user' => ['Cannot delete the last Super Admin user.'],
            ]);
        }

        $subject->delete();
    }

    /**
     * Ensure at least one Super Admin remains.
     *
     * @param  array<int, int>  $roleIds
     *
     * @throws ValidationException
     */
    protected function assertCanChangeRoles(User $user, array $roleIds): void
    {
        $superAdmin = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($superAdmin === null) {
            return;
        }

        if (! $user->hasRole('Super Admin')) {
            return;
        }

        if (in_array($superAdmin->id, $roleIds, true)) {
            return;
        }

        if ($this->isLastSuperAdmin($user)) {
            throw ValidationException::withMessages([
                'role_id' => ['You must keep at least one Super Admin user.'],
            ]);
        }
    }

    protected function isLastSuperAdmin(User $user): bool
    {
        if (! $user->hasRole('Super Admin')) {
            return false;
        }

        return User::query()->role('Super Admin')->count() <= 1;
    }

    /**
     * Form / JSON payloads send role IDs as strings; strict checks need integers.
     *
     * @param  array<int|string>  $roleIds
     * @return array<int>
     */
    protected function normalizeRoleIds(array $roleIds): array
    {
        $ids = array_values(array_unique(array_map(static fn (int|string $id): int => (int) $id, $roleIds)));

        if (count($ids) > 1) {
            throw ValidationException::withMessages([
                'role_id' => ['Only one role can be assigned per user.'],
            ]);
        }

        return $ids;
    }
}
