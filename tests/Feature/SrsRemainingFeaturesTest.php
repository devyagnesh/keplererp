<?php

namespace Tests\Feature;

use App\Mail\VendorPortalCredentialsMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayable;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SrsRemainingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_ok(): void
    {
        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true);
    }

    public function test_vendor_portal_enable_generates_password_and_emails_credentials(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $vendor = Vendor::factory()->create([
            'phone' => '9876543210',
            'email' => 'vendor@example.com',
        ]);

        $this->actingAs($user)->putJson(route('admin.vendors.update', $vendor), [
            'name' => $vendor->name,
            'email' => $vendor->email,
            'phone' => $vendor->phone,
            'address_line1' => $vendor->address_line1,
            'city' => $vendor->city,
            'state_code' => $vendor->state_code,
            'pincode' => $vendor->pincode,
            'portal_enabled' => true,
            'generate_portal_password' => true,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Vendor updated successfully. Portal credentials sent by email.');

        $vendor->refresh();
        $this->assertTrue($vendor->portal_enabled);
        $this->assertNotNull($vendor->portal_password);

        Mail::assertSent(VendorPortalCredentialsMail::class, function (VendorPortalCredentialsMail $mail): bool {
            return $mail->hasTo('vendor@example.com')
                && $mail->vendorCode !== '';
        });
    }

    public function test_sales_dispatch_creates_challan_and_audit_log(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-D', 'name' => 'Dispatch WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-D', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '20', $user->id, []);

        $order = SalesOrder::query()->create([
            'order_number' => 'SO-D1',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'total_amount' => '118.00',
            'created_by' => $user->id,
        ]);
        SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => '2',
            'unit_price' => '50',
            'taxable_value' => '100',
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.dispatch', $order))->assertOk();

        $this->assertDatabaseHas('sales_dispatch_challans', ['sales_order_id' => $order->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sales_order.dispatched']);
    }

    public function test_einvoice_stub_generates_irn_when_enabled(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['einvoice.driver' => 'log']);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24', 'einvoice_enabled' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-INV',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'payment_terms_days' => 30,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertOk();

        $this->assertDatabaseHas('invoices', [
            'sales_order_id' => $order->id,
        ]);
        $this->assertNotNull(\App\Models\Invoice::query()->where('sales_order_id', $order->id)->value('irn'));
    }

    public function test_production_status_change_records_audit_log(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Queue::fake();
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $warehouse = Warehouse::query()->create(['code' => 'WH-P', 'name' => 'P', 'city' => 'Ahd', 'is_active' => true]);
        $item = Item::query()->create([
            'sku' => 'SKU-P', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        $rm = Item::query()->create([
            'sku' => 'RM-P', 'name' => 'RM', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'RAW',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $rm->id, '100', $user->id, []);
        $bom = \App\Models\BillOfMaterial::query()->create([
            'parent_item_id' => $item->id,
            'version' => 1,
            'is_active' => true,
        ]);
        \App\Models\BillOfMaterialLine::query()->create([
            'bill_of_material_id' => $bom->id,
            'component_item_id' => $rm->id,
            'quantity_per' => '1',
        ]);
        $wo = ProductionOrder::query()->create([
            'wo_number' => 'WO-1',
            'item_id' => $item->id,
            'bom_id' => $bom->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '10',
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->putJson(route('admin.production.work-orders.update', $wo), [
            'status' => 'in_progress',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.status_changed',
            'subject_id' => $wo->id,
        ]);
    }

    public function test_dispatch_generates_eway_when_enabled(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['eway.driver' => 'log']);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24', 'eway_enabled' => true]);
        $warehouse = Warehouse::query()->create(['code' => 'WH-E', 'name' => 'E WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-E', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '10', $user->id, []);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-E1',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'total_amount' => '118.00',
            'created_by' => $user->id,
        ]);
        SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => '2',
            'unit_price' => '50',
            'taxable_value' => '100',
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.dispatch', $order))->assertOk();

        $challan = \App\Models\SalesDispatchChallan::query()->where('sales_order_id', $order->id)->first();
        $this->assertNotNull($challan);
        $this->assertNotNull($challan->eway_bill_no);
    }

    public function test_invoice_pdf_download(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-PDF',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'payment_terms_days' => 30,
            'created_by' => $user->id,
        ]);
        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertOk();
        $invoice = \App\Models\Invoice::query()->where('sales_order_id', $order->id)->first();
        $this->assertNotNull($invoice);

        $this->actingAs($user)->get(route('admin.sales.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendor_portal_invoice_upload_and_match(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        config(['eway.match_tolerance' => '1.00']);
        $vendor = Vendor::factory()->create([
            'email' => 'vendor@test.com',
            'portal_enabled' => true,
            'portal_password' => 'password123',
        ]);
        $grn = \App\Models\GoodsReceipt::query()->create([
            'grn_number' => 'GRN-V1',
            'vendor_id' => $vendor->id,
            'warehouse_id' => Warehouse::query()->create(['code' => 'W1', 'name' => 'W', 'city' => 'C', 'is_active' => true])->id,
            'received_at' => now(),
            'status' => 'posted',
        ]);
        $payable = VendorPayable::query()->create([
            'goods_receipt_id' => $grn->id,
            'vendor_id' => $vendor->id,
            'amount' => '1000.00',
            'status' => 'open',
        ]);

        $this->actingAs($vendor, 'vendor')->postJson(route('vendor.portal.vendor-invoices.store'), [
            'vendor_payable_id' => $payable->id,
            'vendor_invoice_number' => 'VINV-001',
            'invoice_date' => now()->toDateString(),
            'amount' => '1000.00',
            'document' => UploadedFile::fake()->create('inv.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->assertDatabaseHas('vendor_invoices', [
            'vendor_payable_id' => $payable->id,
            'match_status' => 'matched',
        ]);
    }

    public function test_vendor_document_upload(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $vendor = Vendor::factory()->create();

        $file = UploadedFile::fake()->create('gst.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson(route('admin.vendors.documents.store', $vendor), [
            'document_type' => 'GST_CERT',
            'document' => $file,
        ])->assertCreated();

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id' => $vendor->id,
            'document_type' => 'GST_CERT',
        ]);
    }
}
