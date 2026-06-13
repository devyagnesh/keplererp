<?php

namespace App\Http\Requests;

use App\Models\ProductionOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionMaterialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('production_order');

        return $order instanceof ProductionOrder
            && ($this->user()?->can('update', $order) ?? false)
            && $order->status === 'in_progress';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.id' => ['required', 'integer'],
            'materials.*.actual_qty' => ['required', 'numeric', 'min:0'],
        ];
    }
}
