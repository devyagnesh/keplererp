<?php

namespace App\Services;

use App\Contracts\Gst\EwayBillDriverInterface;
use App\Models\Company;
use App\Models\SalesDispatchChallan;
use Illuminate\Support\Facades\Log;

/**
 * e-Way bill generation hook (SRS logistics). NIC API pluggable via config driver.
 */
class EwayBillService
{
    public function __construct(
        protected EwayBillDriverInterface $driver
    ) {}

    /**
     * @return array{eway_bill_no: string, eway_qr: string}|null
     */
    public function generateForChallan(SalesDispatchChallan $challan): ?array
    {
        $company = Company::query()->orderBy('id')->first();
        if ($company === null || ! $company->eway_enabled) {
            return null;
        }

        if ($challan->eway_bill_no !== null && $challan->eway_bill_no !== '') {
            return null;
        }

        $result = $this->driver->generate($challan);
        if ($result === null) {
            return null;
        }

        $challan->update([
            'eway_bill_no' => $result['eway_bill_no'],
            'eway_qr' => $result['eway_qr'],
            'eway_generated_at' => now(),
        ]);

        Log::info('eway bill generated', [
            'challan_id' => $challan->id,
            'driver' => config('eway.driver'),
        ]);

        return $result;
    }
}
