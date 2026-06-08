<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests are redirected to the login page when accessing company setup.
     */
    public function test_guest_is_redirected_from_company_setup(): void
    {
        $response = $this->get(route('admin.company.edit'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Authenticated users can view the company setup form.
     */
    public function test_authenticated_user_can_view_company_setup(): void
    {
        $user = $this->makePrivilegedUser();

        $response = $this->actingAs($user)->get(route('admin.company.edit'));

        $response->assertOk();
        $response->assertSee('Company master', false);
    }

    /**
     * Company master can be created via PUT with valid GST data.
     */
    public function test_company_can_be_saved_with_valid_payload(): void
    {
        $user = $this->makePrivilegedUser();

        $payload = [
            'company_name' => 'Acme Manufacturing',
            'legal_name' => 'Acme Manufacturing Pvt Ltd',
            'gstin' => '24AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'address_line1' => 'Plot 12, Industrial Area',
            'address_line2' => null,
            'city' => 'Ahmedabad',
            'state_code' => '24',
            'pincode' => '380015',
            'phone' => '9876543210',
            'email' => 'accounts@acme.com',
            'financial_year_start' => '2026-04-01',
            'currency' => 'INR',
            'invoice_prefix' => 'INV/2026-27/',
            'po_prefix' => 'PO/2026-27/',
            'default_tax_type' => 'CGST_SGST',
            'whatsapp_enabled' => '0',
            'einvoice_enabled' => '0',
            'eway_enabled' => '0',
        ];

        $response = $this->actingAs($user)->putJson(route('admin.company.update'), $payload);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Acme Manufacturing',
            'gstin' => '24AAAAA0000A1Z5',
        ]);
        $this->assertSame(1, Company::query()->count());
    }

    /**
     * Invalid GSTIN is rejected with validation errors.
     */
    public function test_invalid_gstin_is_rejected(): void
    {
        $user = $this->makePrivilegedUser();

        $payload = [
            'company_name' => 'Acme Manufacturing',
            'legal_name' => 'Acme Manufacturing Pvt Ltd',
            'gstin' => 'INVALIDGSTINXX',
            'pan' => 'AAAAA0000A',
            'address_line1' => 'Plot 12, Industrial Area',
            'city' => 'Ahmedabad',
            'state_code' => '24',
            'pincode' => '380015',
            'phone' => '9876543210',
            'email' => 'accounts@acme.com',
            'financial_year_start' => '2026-04-01',
            'currency' => 'INR',
            'invoice_prefix' => 'INV/2026-27/',
            'po_prefix' => 'PO/2026-27/',
            'default_tax_type' => 'CGST_SGST',
            'whatsapp_enabled' => '0',
            'einvoice_enabled' => '0',
            'eway_enabled' => '0',
        ];

        $response = $this->actingAs($user)->putJson(route('admin.company.update'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gstin']);
    }

    /**
     * User with all permissions used for company tests.
     */
    private function makePrivilegedUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }
}
