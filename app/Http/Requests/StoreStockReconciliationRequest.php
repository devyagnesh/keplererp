<?php

namespace App\Http\Requests;

use App\Models\StockReconciliation;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockReconciliation::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
