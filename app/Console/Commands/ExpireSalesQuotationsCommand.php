<?php

namespace App\Console\Commands;

use App\Services\SalesQuotationExpiryService;
use Illuminate\Console\Command;

/**
 * Daily job: set quotation status to expired when past valid_until (SRS §11.1).
 */
class ExpireSalesQuotationsCommand extends Command
{
    protected $signature = 'erp:expire-sales-quotations';

    protected $description = 'Mark draft/sent/accepted quotations as expired when valid_until has passed.';

    public function handle(SalesQuotationExpiryService $expiry): int
    {
        $count = $expiry->expireDueQuotations();

        $this->components->info("Marked {$count} quotation(s) as expired.");

        return self::SUCCESS;
    }
}
