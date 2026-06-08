<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_code' => 'C-'.fake()->unique()->numerify('#####'),
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
            'credit_limit' => 0,
            'credit_used' => 0,
            'payment_terms_days' => 30,
            'notes' => null,
            'status' => CustomerStatus::Active,
            'created_by' => null,
        ];
    }

    /**
     * Blocked customer.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::Blocked,
        ]);
    }
}
