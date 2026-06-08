<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.adjust') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'signed_delta' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'batch_no' => ['nullable', 'string', 'max:50'],
            'serial_no' => ['nullable', 'string', 'max:50'],
        ];
    }
}
