<?php

namespace App\Http\Requests;

use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->route('warehouse');

        return $this->user()?->can('update', $warehouse) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->route('warehouse');

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'is_active' => ['sometimes', 'in:0,1,true,false'],
        ];
    }
}
