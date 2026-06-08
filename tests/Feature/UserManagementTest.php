<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Users without permission cannot open the users list.
     */
    public function test_users_index_forbidden_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    /**
     * Super Admin can view the users list.
     */
    public function test_super_admin_can_view_users_index(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('User accounts', false);
    }

    /**
     * Super Admin can create another user via JSON API.
     */
    public function test_super_admin_can_create_user(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('Super Admin');

        $roleId = Role::query()->where('name', 'Staff')->where('guard_name', 'web')->value('id');
        $this->assertNotNull($roleId);

        $payload = [
            'name' => 'New Operator',
            'email' => 'operator@gmail.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'phone' => '9123456789',
            'whatsapp_number' => null,
            'is_active' => true,
            'role_id' => (int) $roleId,
        ];

        $response = $this->actingAs($actor)->postJson(route('admin.users.store'), $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'operator@gmail.com',
        ]);
    }

    /**
     * Role ID from multipart forms may be a string; Super Admin must still be recognized.
     */
    public function test_super_admin_can_update_user_when_role_id_is_string(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('Super Admin');

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $superAdminRoleId = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->value('id');
        $this->assertNotNull($superAdminRoleId);

        $response = $this->actingAs($actor)->put(route('admin.users.update', $user), [
            'name' => 'Renamed Admin',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'phone' => '9876543210',
            'whatsapp_number' => '',
            'is_active' => '1',
            'role_id' => (string) $superAdminRoleId,
        ]);

        $response->assertOk();
        $this->assertSame('Renamed Admin', $user->fresh()->name);
    }
}
