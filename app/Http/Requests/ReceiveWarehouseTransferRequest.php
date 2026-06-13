<?php

namespace App\Http\Requests;

use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveWarehouseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transfer = $this->route('warehouseTransfer');

        return $transfer instanceof WarehouseTransfer
            && ($this->user()?->can('receive', $transfer) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.qty_received' => ['required', 'numeric', 'min:0'],
            'lines.*.variance_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
