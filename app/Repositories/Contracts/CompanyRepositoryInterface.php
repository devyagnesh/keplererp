<?php

namespace App\Repositories\Contracts;

use App\Models\Company;

/**
 * Data access for the singleton company master record.
 */
interface CompanyRepositoryInterface
{
    /**
     * Return the company row if one exists.
     */
    public function first(): ?Company;

    /**
     * Persist the given company model.
     */
    public function save(Company $company): void;
}
