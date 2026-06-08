<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WhatsAppLog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WhatsAppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_po_final_approval_writes_whatsapp_log_when_company_toggle_on(): void
    {
        Config::set('whatsapp.driver', 'log');
        $this->seed(RolePermissionSeeder::class);

        Company::factory()->create(['whatsapp_enabled' => true]);

        $creator = User::factory()->create();
        $creator->assignRole('Super Admin');
        $approver = User::factory()->create();
        $approver->assignRole('Super Admin');

        $warehouse = Warehouse::query()->create([
            'code' => 'WH-WA',
            'name' => 'WH',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-WA',
            'name' => 'Item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);
        $vendor = Vendor::factory()->create(['phone' => '9876543210', 'state_code' => '24']);

        $this->actingAs($creator)->postJson(route('admin.purchase.orders.store'), [
            'vendor_id' => $vendor->id,
            'pr_id' => null,
            'warehouse_id' => $warehouse->id,
            'order_date' => '2026-04-20',
            'expected_delivery' => '2026-04-25',
            'payment_terms_days' => 30,
            'notes' => null,
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 1, 'unit_cost' => 100],
            ],
        ])->assertCreated();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->actingAs($approver)->postJson(route('admin.purchase.orders.approve', $po))->assertOk();

        $this->assertDatabaseHas('whatsapp_logs', [
            'event_type' => 'po_approved',
            'template_name' => 'po_approved',
            'status' => WhatsAppLog::STATUS_SENT,
        ]);
    }

    public function test_pr_rejected_notifies_requester_whatsapp_when_configured(): void
    {
        Config::set('whatsapp.driver', 'log');
        $this->seed(RolePermissionSeeder::class);

        Company::factory()->create(['whatsapp_enabled' => true]);

        $requester = User::factory()->create(['whatsapp_number' => '9876501234']);
        $requester->assignRole('Purchase Manager');

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $warehouse = Warehouse::query()->create([
            'code' => 'WH-PR',
            'name' => 'WH',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-PR',
            'name' => 'Item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);

        $this->actingAs($requester)->postJson(route('admin.purchase.requisitions.store'), [
            'required_date' => '2026-05-01',
            'warehouse_id' => $warehouse->id,
            'notes' => null,
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $pr = PurchaseRequisition::query()->latest('id')->firstOrFail();
        $this->actingAs($requester)->postJson(route('admin.purchase.requisitions.submit', $pr))->assertOk();

        $this->actingAs($admin)->postJson(route('admin.purchase.requisitions.reject', $pr), [
            'rejected_reason' => 'Budget hold',
        ])->assertOk();

        $this->assertDatabaseHas('whatsapp_logs', [
            'event_type' => 'pr_rejected',
            'template_name' => 'pr_rejected',
            'status' => WhatsAppLog::STATUS_SENT,
        ]);
    }
}
