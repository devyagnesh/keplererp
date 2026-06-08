<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;

/**
 * Eloquent implementation of {@see CompanyRepositoryInterface}.
 */
class CompanyRepository implements CompanyRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function first(): ?Company
    {
        return Company::query()
            ->select([
                'id',
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
                'created_at',
                'updated_at',
            ])
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function save(Company $company): void
    {
        $company->save();
    }
}
