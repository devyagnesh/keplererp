<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Item $item */
        $item = $this->route('item');

        return $this->user()?->can('update', $item) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('items', 'sku')->ignore($item->id)],
            'name' => ['required', 'string', 'max:191'],
            'uom' => ['required', 'string', 'max:16'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'in:0,1,true,false'],
            'is_batch_tracked' => ['sometimes', 'boolean'],
            'is_serial_tracked' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            if ($this->boolean('is_batch_tracked') && $this->boolean('is_serial_tracked')) {
                $v->errors()->add('is_serial_tracked', 'An item cannot be both batch- and serial-tracked.');
            }
        });
    }
}
