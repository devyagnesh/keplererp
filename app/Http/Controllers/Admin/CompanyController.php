<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Company master (Module 1 — Company & System Setup).
 */
class CompanyController extends Controller
{
    public function __construct(
        protected CompanyRepositoryInterface $companies,
        protected CompanyService $companyService
    ) {}

    /**
     * Show the company setup form.
     */
    public function edit(): View
    {
        $this->authorize('viewAny', Company::class);

        $company = $this->companies->first();

        return view('admin.company.edit', [
            'company' => $company,
            'gstStates' => config('gst.state_codes', []),
        ]);
    }

    /**
     * Persist company master data (AJAX).
     */
    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        try {
            $company = $this->companyService->upsertFromRequest($request);

            return response()->json([
                'status' => true,
                'message' => 'Company settings saved successfully.',
                'data' => [
                    'id' => $company->id,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('CompanyController@update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while saving company settings.',
            ], 500);
        }
    }
}
