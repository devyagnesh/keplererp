<?php

namespace App\Services;

use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Business logic for company master (logo handling, transactional save).
 */
class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $companies
    ) {}

    /**
     * Create or update the singleton company from validated input.
     *
     * @throws Throwable
     */
    public function upsertFromRequest(UpdateCompanyRequest $request): Company
    {
        return DB::transaction(function () use ($request): Company {
            $company = $this->companies->first() ?? new Company;
            $data = $request->safe()->except(['logo']);

            $company->fill($data);

            /** @var UploadedFile|null $logo */
            $logo = $request->file('logo');
            if ($logo !== null) {
                $this->replaceLogo($company, $logo);
            }

            $this->companies->save($company);

            return $company->fresh() ?? $company;
        });
    }

    /**
     * Store a new logo on the public disk and remove the previous file when present.
     */
    protected function replaceLogo(Company $company, UploadedFile $logo): void
    {
        $directory = 'company-logos';
        $filename = Str::uuid()->toString().'.'.$logo->getClientOriginalExtension();
        $path = $logo->storeAs($directory, $filename, 'public');

        if ($path === false) {
            Log::error('CompanyService: failed to store company logo.');

            throw new \RuntimeException('Could not store logo file.');
        }

        $previous = $company->logo;
        $company->logo = $path;

        if ($previous !== null && $previous !== $path && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }
    }
}
