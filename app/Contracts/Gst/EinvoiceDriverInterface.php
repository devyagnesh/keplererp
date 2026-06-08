<?php

namespace App\Contracts\Gst;

use App\Models\Invoice;

/**
 * Pluggable e-Invoice IRN generation (NIC / GSP).
 */
interface EinvoiceDriverInterface
{
    /**
     * Generate IRN for a posted B2B invoice.
     *
     * @return array{irn: string, ack_no: string, qr: string}|null Null when skipped or failed.
     */
    public function generate(Invoice $invoice): ?array;
}
