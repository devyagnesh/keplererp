<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * User & RBAC management (Spatie roles on users).
 */
class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected UserService $userService
    ) {}

    /**
     * Users listing (DataTables loads via {@see data()}).
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index');
    }

    /**
     * Server-side DataTables JSON.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $payload = $this->users->getDataTableRows($request);
        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (User $user) use ($actor) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->implode(', '),
                'is_active' => $user->is_active ? 'Yes' : 'No',
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($user, $actor),
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
     * Build action buttons HTML for the current actor.
     */
    protected function buildActionHtml(User $user, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';

        if ($actor->can('update', $user)) {
            $html .= '<a href="'.e(route('admin.users.edit', $user)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }

        if ($actor->can('delete', $user)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-delete-user" data-delete-url="'.e(route('admin.users.destroy', $user)).'">Delete</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Show create user form.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return view('admin.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $roleIds = [(int) $validated['role_id']];
            $data = collect($validated)->except(['role_id', 'password_confirmation'])->all();
            $user = $this->userService->create($data, $roleIds);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully.',
                'data' => ['id' => $user->id],
            ], 201);
        } catch (Throwable $e) {
            Log::error('UserController@store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create user.',
            ], 500);
        }
    }

    /**
     * Show edit user form.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles:id,name']);

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validated();
            $roleIds = [(int) $validated['role_id']];
            $data = collect($validated)->except(['role_id', 'password_confirmation'])->all();
            $this->userService->update($user, $data, $roleIds);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('UserController@update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update user.',
            ], 500);
        }
    }

    /**
     * Soft-delete the given user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $actor = request()->user();
        if ($actor === null) {
            abort(403);
        }

        try {
            $this->userService->delete($user, $actor);

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('UserController@destroy failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete user.',
            ], 500);
        }
    }
}
