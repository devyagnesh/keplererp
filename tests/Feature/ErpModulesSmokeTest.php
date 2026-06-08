<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpModulesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_inventory_and_purchase_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.warehouses.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.items.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.inventory.balances.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.purchase.requisitions.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.purchase.orders.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.sales.quotations.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_staff_cannot_open_warehouses_without_inventory_view(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $this->actingAs($user)->get(route('admin.warehouses.index'))->assertForbidden();
    }
}
