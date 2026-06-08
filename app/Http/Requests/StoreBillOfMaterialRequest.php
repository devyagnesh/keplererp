<?php

namespace App\Http\Requests;

use App\Models\BillOfMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBillOfMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BillOfMaterial::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['component_item_id'])
                && isset($line['quantity_per']) && $line['quantity_per'] !== '')
            ->values()
            ->all();
        $this->merge(['lines' => $lines]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_item_id' => ['required', 'integer', 'exists:items,id'],
            'version' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('bill_of_materials', 'version')->where(
                    'parent_item_id',
                    (int) $this->input('parent_item_id')
                ),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.component_item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity_per' => ['required', 'numeric', 'min:0.000001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $parent = (int) $this->input('parent_item_id');
            foreach ($this->input('lines', []) as $index => $line) {
                if ((int) ($line['component_item_id'] ?? 0) === $parent) {
                    $v->errors()->add(
                        'lines.'.$index.'.component_item_id',
                        'A component cannot be the same as the parent item.'
                    );
                }
            }
        });
    }
}
