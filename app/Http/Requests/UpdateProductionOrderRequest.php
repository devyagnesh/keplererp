<?php

namespace App\Http\Requests;

use App\Models\ProductionOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProductionOrder $productionOrder */
        $productionOrder = $this->route('production_order');

        return $this->user()?->can('update', $productionOrder) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
            'actual_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
