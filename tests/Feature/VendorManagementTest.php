<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Users without vendors.view cannot open the vendors list.
     */
    public function test_vendors_index_forbidden_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route('admin.vendors.index'));

        $response->assertForbidden();
    }

    /**
     * Purchase Manager can view the vendors list.
     */
    public function test_purchase_manager_can_view_vendors_index(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Purchase Manager');

        $response = $this->actingAs($user)->get(route('admin.vendors.index'));

        $response->assertOk();
        $response->assertSee('Vendors', false);
    }

    /**
     * Purchase Manager can create a vendor via JSON API.
     */
    public function test_purchase_manager_can_create_vendor(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('Purchase Manager');

        $payload = [
            'name' => 'Acme Supplies Pvt Ltd',
            'contact_person' => 'R. Kumar',
            'email' => 'purchase@acmesupplies.com',
            'phone' => '9876543210',
            'gstin' => '24AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'address_line1' => 'Plot 5, MIDC',
            'address_line2' => null,
            'city' => 'Ahmedabad',
            'state_code' => '24',
            'pincode' => '380015',
            'payment_terms' => 'Net 30',
            'notes' => null,
        ];

        $response = $this->actingAs($actor)->postJson(route('admin.vendors.store'), $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('vendors', [
            'name' => 'Acme Supplies Pvt Ltd',
            'phone' => '9876543210',
        ]);
        $this->assertSame(1, Vendor::query()->count());
    }
}
