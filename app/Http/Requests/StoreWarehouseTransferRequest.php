<?php

namespace App\Http\Requests;

use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WarehouseTransfer::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_requested' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:64'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:64'],
        ];
    }
}
