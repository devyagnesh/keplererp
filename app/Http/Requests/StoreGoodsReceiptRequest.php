<?php

namespace App\Http\Requests;

use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Services\BatchSerialInventoryService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GoodsReceipt::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['item_id'])
                && isset($line['quantity']) && $line['quantity'] !== '')
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
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'qc_officer_name' => ['nullable', 'string', 'max:120'],
            'qc_photo' => ['nullable', 'image', 'max:5120'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.accepted_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.rejected_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qc_status' => ['nullable', 'string', 'in:pass,fail,hold'],
            'lines.*.qc_remarks' => ['nullable', 'string', 'max:500'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:50'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:50'],
            'lines.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach ($this->input('lines', []) as $index => $line) {
                if (! is_array($line) || empty($line['item_id']) || ! isset($line['quantity']) || $line['quantity'] === '') {
                    continue;
                }
                $qty = (string) $line['quantity'];
                $accepted = isset($line['accepted_qty']) && $line['accepted_qty'] !== ''
                    ? (string) $line['accepted_qty']
                    : $qty;
                $rejected = isset($line['rejected_qty']) && $line['rejected_qty'] !== ''
                    ? (string) $line['rejected_qty']
                    : '0';
                if (bccomp(bcadd($accepted, $rejected, 4), $qty, 4) !== 0) {
                    $v->errors()->add(
                        'lines.'.$index.'.quantity',
                        'Accepted quantity plus rejected quantity must equal the line quantity.'
                    );
                }

                $item = Item::query()->find((int) $line['item_id']);
                if ($item === null) {
                    continue;
                }
                try {
                    $batchSerial = app(BatchSerialInventoryService::class);
                    $batchSerial->validateInboundLine($item, [
                        'batch_no' => $line['batch_no'] ?? null,
                        'serial_no' => $line['serial_no'] ?? null,
                        'quantity' => $accepted,
                    ]);
                    if ($item->is_batch_tracked && empty($line['expiry_date'])) {
                        $v->errors()->add('lines.'.$index.'.expiry_date', "Expiry date is required for batch-tracked item {$item->sku}.");
                    }
                } catch (\InvalidArgumentException $e) {
                    $v->errors()->add('lines.'.$index.'.batch_no', $e->getMessage());
                }
            }
        });
    }
}
