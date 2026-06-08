<?php

namespace App\Models;

use App\Enums\DefaultTaxType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company master — single-tenant row for on-premise ManufactureERP deployment.
 *
 * @property int $id
 * @property string $company_name
 * @property string $legal_name
 * @property string $gstin
 * @property string $pan
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string $state_code
 * @property string $pincode
 * @property string $phone
 * @property string $email
 * @property string|null $logo
 * @property \Illuminate\Support\Carbon $financial_year_start
 * @property string $currency
 * @property string $invoice_prefix
 * @property string $po_prefix
 * @property DefaultTaxType $default_tax_type
 * @property bool $whatsapp_enabled
 * @property bool $einvoice_enabled
 * @property bool $eway_enabled
 * @property string|null $po_approval_threshold
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'legal_name',
        'gstin',
        'pan',
        'address_line1',
        'address_line2',
        'city',
        'state_code',
        'pincode',
        'phone',
        'email',
        'logo',
        'financial_year_start',
        'currency',
        'invoice_prefix',
        'po_prefix',
        'default_tax_type',
        'whatsapp_enabled',
        'einvoice_enabled',
        'eway_enabled',
        'po_approval_threshold',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'financial_year_start' => 'date',
            'whatsapp_enabled' => 'boolean',
            'einvoice_enabled' => 'boolean',
            'eway_enabled' => 'boolean',
            'default_tax_type' => DefaultTaxType::class,
            'po_approval_threshold' => 'decimal:2',
        ];
    }
}
