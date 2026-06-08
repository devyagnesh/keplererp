<?php

namespace Database\Factories;

use App\Enums\DefaultTaxType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @var class-string<Company>
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'legal_name' => fake()->company().' Pvt Ltd',
            'gstin' => '24AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'state_code' => '24',
            'pincode' => fake()->regexify('[1-9][0-9]{5}'),
            'phone' => fake()->regexify('[6-9][0-9]{9}'),
            'email' => fake()->companyEmail(),
            'logo' => null,
            'financial_year_start' => now()->month >= 4 ? now()->startOfYear()->addMonths(3)->startOfDay() : now()->subYear()->startOfYear()->addMonths(3)->startOfDay(),
            'currency' => 'INR',
            'invoice_prefix' => 'INV/'.now()->format('Y').'-'.now()->addYear()->format('y').'/',
            'po_prefix' => 'PO/'.now()->format('Y').'-'.now()->addYear()->format('y').'/',
            'default_tax_type' => DefaultTaxType::CGST_SGST,
            'whatsapp_enabled' => false,
            'einvoice_enabled' => false,
            'eway_enabled' => false,
            'po_approval_threshold' => null,
        ];
    }
}
