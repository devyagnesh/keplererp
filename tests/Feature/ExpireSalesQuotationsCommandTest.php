<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesQuotation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExpireSalesQuotationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_past_valid_until_quotations_as_expired(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $open = SalesQuotation::query()->create([
            'quote_number' => 'QT-OPEN',
            'customer_id' => $customer->id,
            'quote_date' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $stillValid = SalesQuotation::query()->create([
            'quote_number' => 'QT-OK',
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'valid_until' => now()->addWeek()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $converted = SalesQuotation::query()->create([
            'quote_number' => 'QT-DONE',
            'customer_id' => $customer->id,
            'quote_date' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->subDays(2)->toDateString(),
            'status' => 'converted',
            'created_by' => $user->id,
        ]);

        Artisan::call('erp:expire-sales-quotations');

        $this->assertSame('expired', $open->fresh()->status);
        $this->assertSame('sent', $stillValid->fresh()->status);
        $this->assertSame('converted', $converted->fresh()->status);
    }

    public function test_expired_quotation_via_scheduler_cannot_convert(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $customer = Customer::factory()->create();
        $quotation = SalesQuotation::query()->create([
            'quote_number' => 'QT-SCHED',
            'customer_id' => $customer->id,
            'quote_date' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        Artisan::call('erp:expire-sales-quotations');
        $this->assertSame('expired', $quotation->fresh()->status);

        $warehouse = \App\Models\Warehouse::query()->create([
            'code' => 'WH-X', 'name' => 'WH', 'city' => 'Ahd', 'is_active' => true,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.quotations.convert', $quotation), [
            'warehouse_id' => $warehouse->id,
        ])->assertForbidden();
    }
}
