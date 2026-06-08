<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesDispatchChallan;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\EinvoiceService;
use App\Services\EwayBillService;
use App\Services\Gst\Drivers\NicEinvoiceDriver;
use App\Services\Gst\Drivers\NicEwayBillDriver;
use App\Services\InventoryStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GstNicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'einvoice.driver' => 'nic',
            'einvoice.nic' => [
                'base_url' => 'https://gst-api.test',
                'auth_path' => '/authenticate',
                'generate_path' => '/einvoice/generate',
                'username' => 'user',
                'password' => 'pass',
                'client_id' => 'cid',
                'client_secret' => 'secret',
                'gstin' => '24AAAAA0000A1Z5',
                'timeout_seconds' => 5,
                'token_ttl_seconds' => 3600,
            ],
            'eway.driver' => 'nic',
            'eway.nic' => [
                'base_url' => 'https://gst-api.test',
                'auth_path' => '/authenticate',
                'generate_path' => '/ewaybill/generate',
                'username' => 'user',
                'password' => 'pass',
                'client_id' => 'cid',
                'client_secret' => 'secret',
                'gstin' => '24AAAAA0000A1Z5',
                'timeout_seconds' => 5,
                'token_ttl_seconds' => 3600,
            ],
        ]);
    }

    public function test_nic_einvoice_driver_returns_irn_from_api(): void
    {
        Http::fake([
            'https://gst-api.test/authenticate' => Http::response(['access_token' => 'tok-abc'], 200),
            'https://gst-api.test/einvoice/generate' => Http::response([
                'Status' => '1',
                'Data' => [
                    'Irn' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4',
                    'AckNo' => '12345678901234',
                    'SignedQRCode' => 'base64qrpayload',
                ],
            ], 200),
        ]);

        Company::factory()->create(['einvoice_enabled' => true]);
        $customer = Customer::factory()->create(['gstin' => '24BBBBB0000B1Z5', 'state_code' => '24']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-NIC-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-NIC',
            'name' => 'Widget',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '84713010',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-NIC-1',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '100.00',
            'discount_amount' => '0.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'status' => 'posted',
        ]);
        $invoice->invoiceItems()->create([
            'item_id' => $item->id,
            'quantity' => '1',
            'unit_price' => '100',
            'taxable_value' => '100',
            'cgst' => '9',
            'sgst' => '9',
            'igst' => '0',
        ]);

        $result = app(NicEinvoiceDriver::class)->generate($invoice);

        $this->assertNotNull($result);
        $this->assertSame('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', $result['irn']);
        $this->assertSame('12345678901234', $result['ack_no']);
        $this->assertSame('base64qrpayload', $result['qr']);
    }

    public function test_einvoice_service_persists_nic_irn(): void
    {
        Http::fake([
            'https://gst-api.test/*' => Http::sequence()
                ->push(['access_token' => 'tok'], 200)
                ->push([
                    'Irn' => 'live-irn-hash-64chars-placeholder-for-test-only-xx',
                    'AckNo' => 'ACK-LIVE-1',
                    'SignedQRCode' => 'qr-live',
                ], 200),
        ]);

        Company::factory()->create(['einvoice_enabled' => true]);
        $customer = Customer::factory()->create(['gstin' => '24BBBBB0000B1Z5']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-SVC-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '50',
            'taxable_amount' => '50',
            'cgst_amount' => '4.5',
            'sgst_amount' => '4.5',
            'igst_amount' => '0',
            'total_amount' => '59',
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-SVC-1',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '50',
            'discount_amount' => '0',
            'taxable_amount' => '50',
            'cgst_amount' => '4.5',
            'sgst_amount' => '4.5',
            'igst_amount' => '0',
            'total_amount' => '59',
            'status' => 'posted',
        ]);

        app(EinvoiceService::class)->generateForInvoice($invoice);

        $invoice->refresh();
        $this->assertSame('live-irn-hash-64chars-placeholder-for-test-only-xx', $invoice->irn);
        $this->assertSame('ACK-LIVE-1', $invoice->ack_no);
        $this->assertNotNull($invoice->irn_generated_at);
    }

    public function test_nic_eway_driver_returns_bill_number_from_api(): void
    {
        Http::fake([
            'https://gst-api.test/authenticate' => Http::response(['token' => 'eway-tok'], 200),
            'https://gst-api.test/ewaybill/generate' => Http::response([
                'ewayBillNo' => '391234567890',
                'SignedQRCode' => 'eway-qr-data',
            ], 200),
        ]);

        Company::factory()->create(['eway_enabled' => true]);
        $customer = Customer::factory()->create(['state_code' => '27']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-N', 'name' => 'Main', 'city' => 'Ahd', 'is_active' => true]);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-EWB',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100',
            'taxable_amount' => '100',
            'cgst_amount' => '0',
            'sgst_amount' => '0',
            'igst_amount' => '18',
            'total_amount' => '118',
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-EWB', 'name' => 'Part', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '84713010', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => '2',
            'unit_price' => '50',
            'taxable_value' => '100',
        ]);
        $challan = SalesDispatchChallan::query()->create([
            'challan_number' => 'DC-NIC-1',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'dispatched_at' => now(),
        ]);

        $result = app(NicEwayBillDriver::class)->generate($challan);

        $this->assertNotNull($result);
        $this->assertSame('391234567890', $result['eway_bill_no']);
        $this->assertSame('eway-qr-data', $result['eway_qr']);
    }

    public function test_nic_einvoice_skips_customer_without_gstin(): void
    {
        Http::fake();

        Company::factory()->create();
        $customer = Customer::factory()->create(['gstin' => null]);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-B2C',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '10',
            'taxable_amount' => '10',
            'cgst_amount' => '0.9',
            'sgst_amount' => '0.9',
            'igst_amount' => '0',
            'total_amount' => '11.8',
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-B2C',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '10',
            'discount_amount' => '0',
            'taxable_amount' => '10',
            'cgst_amount' => '0.9',
            'sgst_amount' => '0.9',
            'igst_amount' => '0',
            'total_amount' => '11.8',
            'status' => 'posted',
        ]);

        $result = app(NicEinvoiceDriver::class)->generate($invoice);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_dispatch_with_nic_eway_driver_via_http_fake(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Http::fake([
            'https://gst-api.test/*' => Http::sequence()
                ->push(['access_token' => 't1'], 200)
                ->push(['ewayBillNo' => '991122334455', 'SignedQRCode' => 'qr-ewb'], 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24', 'eway_enabled' => true]);
        $warehouse = Warehouse::query()->create(['code' => 'WH-L', 'name' => 'Live WH', 'city' => 'Ahd', 'is_active' => true]);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-L', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '10', $user->id, []);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-LIVE-EWB',
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

        $challan = SalesDispatchChallan::query()->where('sales_order_id', $order->id)->first();
        $this->assertNotNull($challan);
        $this->assertSame('991122334455', $challan->eway_bill_no);
    }
}
