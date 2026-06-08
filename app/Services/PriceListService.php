<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PriceListItem;

/**
 * Resolves customer-specific unit prices from price lists (SRS sales pricing).
 */
class PriceListService
{
    public function unitPriceForCustomer(Customer $customer, Item $item, string $fallback): string
    {
        if ($customer->price_list_id === null) {
            return $fallback;
        }

        $price = PriceListItem::query()
            ->where('price_list_id', $customer->price_list_id)
            ->where('item_id', $item->id)
            ->value('unit_price');

        return $price !== null ? (string) $price : $fallback;
    }
}
