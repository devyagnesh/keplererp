<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_code' => 'V-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->regexify('[6-9][0-9]{9}'),
            'gstin' => '24AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => 'Ahmedabad',
            'state_code' => '24',
            'pincode' => '380015',
            'payment_terms' => 'Net 30',
            'notes' => null,
            'status' => VendorStatus::Active,
            'approved_at' => now(),
            'approved_by' => null,
            'created_by' => null,
        ];
    }

    /**
     * Pending approval (no GST sample — set in test if needed).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VendorStatus::PendingApproval,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }
}
