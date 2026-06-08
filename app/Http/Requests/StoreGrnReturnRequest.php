<?php

namespace App\Http\Requests;

use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Services\BatchSerialInventoryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGrnReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'goods_receipt_id' => ['required', 'integer', 'exists:goods_receipts,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'debit_amount' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $grn = GoodsReceipt::query()->with('lines')->find((int) $this->input('goods_receipt_id'));
            if ($grn === null) {
                return;
            }
            $warehouseId = (int) $grn->warehouse_id;
            $batchSerial = app(BatchSerialInventoryService::class);

            foreach ($this->input('lines', []) as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $item = Item::query()->find((int) ($line['item_id'] ?? 0));
                if ($item === null) {
                    continue;
                }
                try {
                    $batchSerial->validateOutboundLine($item, $warehouseId, [
                        'batch_no' => $line['batch_no'] ?? null,
                        'serial_no' => null,
                        'quantity' => (string) ($line['quantity'] ?? '0'),
                    ]);
                } catch (\InvalidArgumentException $e) {
                    $v->errors()->add('lines.'.$index.'.batch_no', $e->getMessage());
                }
            }
        });
    }
}
