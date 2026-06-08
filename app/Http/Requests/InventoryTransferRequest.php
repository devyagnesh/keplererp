<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.transfer') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'batch_no' => ['nullable', 'string', 'max:50'],
            'serial_no' => ['nullable', 'string', 'max:50'],
        ];
    }
}
