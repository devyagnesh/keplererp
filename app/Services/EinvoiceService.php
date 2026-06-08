<?php

namespace App\Services;

use App\Contracts\Gst\EinvoiceDriverInterface;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * e-Invoice IRN generation hook (SRS US-07). NIC/IRP integration via configurable driver.
 */
class EinvoiceService
{
    public function __construct(
        protected EinvoiceDriverInterface $driver
    ) {}

    /**
     * Attempt to generate IRN for a posted invoice when company toggle and driver allow.
     *
     * @return array{irn: string, ack_no: string, qr: string}|null
     */
    public function generateForInvoice(Invoice $invoice): ?array
    {
        $company = Company::query()->orderBy('id')->first();
        if ($company === null || ! $company->einvoice_enabled) {
            return null;
        }

        if ($invoice->irn !== null && $invoice->irn !== '') {
            return null;
        }

        $result = $this->driver->generate($invoice);
        if ($result === null) {
            return null;
        }

        $invoice->update([
            'irn' => $result['irn'],
            'ack_no' => $result['ack_no'],
            'einvoice_qr' => $result['qr'],
            'irn_generated_at' => now(),
        ]);

        Log::info('einvoice IRN generated', [
            'invoice_id' => $invoice->id,
            'driver' => config('einvoice.driver'),
        ]);

        return $result;
    }
}
