<?php

namespace App\Enums;

/**
 * Default GST presentation for master data (invoice-time logic still compares buyer/seller states).
 */
enum DefaultTaxType: string
{
    case IGST = 'IGST';
    case CGST_SGST = 'CGST_SGST';

    /**
     * Human-readable label for selects.
     */
    public function label(): string
    {
        return match ($this) {
            self::IGST => 'IGST (inter-state default)',
            self::CGST_SGST => 'CGST + SGST (intra-state default)',
        };
    }
}
