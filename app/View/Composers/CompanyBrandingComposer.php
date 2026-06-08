<?php

namespace App\View\Composers;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Shares company logo URL and display name for layouts (login, sidebar, header).
 */
class CompanyBrandingComposer
{
    /**
     * Bind company branding data to the view.
     */
    public function compose(View $view): void
    {
        $payload = $this->resolve();

        $view->with('companyLogoUrl', $payload['logoUrl']);
        $view->with('companyDisplayName', $payload['displayName']);
    }

    /**
     * @return array{logoUrl: string|null, displayName: string}
     */
    protected function resolve(): array
    {
        /** @var array{logoUrl: string|null, displayName: string}|null $cache */
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $company = Company::query()
            ->select(['company_name', 'logo'])
            ->first();

        $logoUrl = null;
        if ($company?->logo !== null && $company->logo !== '') {
            $logoUrl = Storage::disk('public')->url($company->logo);
        }

        $cache = [
            'logoUrl' => $logoUrl,
            'displayName' => $company?->company_name ?? 'ManufactureERP',
        ];

        return $cache;
    }
}
