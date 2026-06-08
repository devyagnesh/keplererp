<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Users without customers.view cannot open the customers list.
     */
    public function test_customers_index_forbidden_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route('admin.customers.index'));

        $response->assertForbidden();
    }

    /**
     * Sales Manager can view the customers list.
     */
    public function test_sales_manager_can_view_customers_index(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Sales Manager');

        $response = $this->actingAs($user)->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertSee('Customers', false);
    }

    /**
     * Sales Manager can create a customer via JSON API.
     */
    public function test_sales_manager_can_create_customer(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('Sales Manager');

        $payload = [
            'name' => 'Retail Buyer Co',
            'contact_person' => 'S. Mehta',
            'email' => 'orders@retailbuyer.com',
            'phone' => '9123456789',
            'gstin' => '24AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'address_line1' => 'Shop 12, MG Road',
            'address_line2' => null,
            'city' => 'Ahmedabad',
            'state_code' => '24',
            'pincode' => '380015',
            'payment_terms' => 'Net 15',
            'notes' => null,
        ];

        $response = $this->actingAs($actor)->postJson(route('admin.customers.store'), $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'name' => 'Retail Buyer Co',
            'phone' => '9123456789',
            'status' => 'active',
        ]);
        $this->assertSame(1, Customer::query()->count());
    }
}
