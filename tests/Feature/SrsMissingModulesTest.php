<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationLine;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayable;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SrsMissingModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_quotation_converts_to_sales_order(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-Q', 'name' => 'Q WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-Q', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '50', $user->id, []);

        $quotation = SalesQuotation::query()->create([
            'quote_number' => 'QT-001',
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);
        SalesQuotationLine::query()->create([
            'sales_quotation_id' => $quotation->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 100,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertCreated();

        $quotation->refresh();
        $this->assertSame('converted', $quotation->status);
        $this->assertDatabaseHas('sales_orders', ['quotation_id' => $quotation->id, 'status' => 'confirmed']);
    }

    public function test_customer_receipt_clears_invoice_balance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::query()->create([
            'order_number' => 'SO-T1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'subtotal' => '1000',
            'discount_amount' => '0',
            'taxable_amount' => '1000',
            'cgst_amount' => '90',
            'sgst_amount' => '90',
            'igst_amount' => '0',
            'total_amount' => '1180',
            'payment_terms_days' => 30,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-T1',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '1000',
            'discount_amount' => '0',
            'taxable_amount' => '1000',
            'cgst_amount' => '90',
            'sgst_amount' => '90',
            'igst_amount' => '0',
            'total_amount' => '1180',
            'amount_paid' => '0',
            'status' => 'posted',
        ]);

        $this->actingAs($user)->postJson(route('admin.finance.payments.customer'), [
            'invoice_id' => $invoice->id,
            'amount' => '1180',
            'payment_method' => 'NEFT',
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0, bccomp('1180', (string) $invoice->amount_paid, 2));
    }

    public function test_gstr1_export_returns_csv(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.reports.gstr1', ['year' => now()->year, 'month' => now()->month]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_expired_quotation_cannot_convert(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-E', 'name' => 'WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-E', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        $quotation = SalesQuotation::query()->create([
            'quote_number' => 'QT-EXP',
            'customer_id' => $customer->id,
            'quote_date' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);
        SalesQuotationLine::query()->create([
            'sales_quotation_id' => $quotation->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertUnprocessable();

        $this->assertSame('sent', $quotation->fresh()->status);

        \Illuminate\Support\Facades\Artisan::call('erp:expire-sales-quotations');
        $this->assertSame('expired', $quotation->fresh()->status);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertForbidden();
    }

    public function test_quotation_convert_fails_when_insufficient_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-NS', 'name' => 'WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-NS', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        $quotation = SalesQuotation::query()->create([
            'quote_number' => 'QT-NS',
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);
        SalesQuotationLine::query()->create([
            'sales_quotation_id' => $quotation->id,
            'item_id' => $item->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('sales_orders', ['quotation_id' => $quotation->id]);
        $this->assertNotSame('converted', $quotation->fresh()->status);
    }

    public function test_customer_receipt_rejects_overpayment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::query()->create([
            'order_number' => 'SO-T2',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'subtotal' => '100',
            'discount_amount' => '0',
            'taxable_amount' => '100',
            'cgst_amount' => '0',
            'sgst_amount' => '0',
            'igst_amount' => '0',
            'total_amount' => '100',
            'payment_terms_days' => 30,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-T2',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '100',
            'discount_amount' => '0',
            'taxable_amount' => '100',
            'cgst_amount' => '0',
            'sgst_amount' => '0',
            'igst_amount' => '0',
            'total_amount' => '100',
            'amount_paid' => '0',
            'status' => 'posted',
        ]);

        $this->actingAs($user)->postJson(route('admin.finance.payments.customer'), [
            'invoice_id' => $invoice->id,
            'amount' => '150',
            'payment_method' => 'NEFT',
            'payment_date' => now()->toDateString(),
        ])->assertUnprocessable();
    }

    public function test_quotation_convert_respects_credit_limit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Sales Manager');
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-CL', 'name' => 'WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create([
            'state_code' => '24',
            'credit_limit' => '500.00',
            'credit_used' => '0',
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-CL', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '50', $user->id, []);
        $quotation = SalesQuotation::query()->create([
            'quote_number' => 'QT-CL',
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);
        SalesQuotationLine::query()->create([
            'sales_quotation_id' => $quotation->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertUnprocessable();
    }
}
