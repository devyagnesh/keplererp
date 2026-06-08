<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use InvalidArgumentException;

/**
 * SRS credit-limit checks for sales orders and quotation conversion.
 */
class CustomerCreditService
{
    /**
     * @throws InvalidArgumentException
     */
    public function assertWithinLimit(Customer $customer, string $orderTotal, ?User $actor, bool $overrideRequested): void
    {
        if (bccomp((string) $customer->credit_limit, '0', 2) <= 0) {
            return;
        }

        if ($actor !== null && $actor->can('company.edit') && $overrideRequested) {
            return;
        }

        $used = (string) $customer->credit_used;
        if (bccomp(bcadd($used, $orderTotal, 2), (string) $customer->credit_limit, 2) > 0) {
            throw new InvalidArgumentException('Order total exceeds the customer credit limit.');
        }
    }
}
