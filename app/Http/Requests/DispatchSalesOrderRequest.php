<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\SalesOrder;
use App\Services\BatchSerialInventoryService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DispatchSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SalesOrder|null $order */
        $order = $this->route('sales_order');

        return $order !== null && ($this->user()?->can('dispatch', $order) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lines' => ['sometimes', 'array'],
            'lines.*.line_id' => ['required_with:lines', 'integer'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:50'],
            'lines.*.serial_no' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var SalesOrder $order */
            $order = $this->route('sales_order');
            $order->loadMissing(['lines.item']);
            $warehouseId = (int) $order->warehouse_id;
            if ($warehouseId <= 0) {
                $v->errors()->add('warehouse_id', 'Sales order warehouse is required for dispatch.');

                return;
            }

            $allocations = collect($this->input('lines', []))->keyBy('line_id');
            $batchSerial = app(BatchSerialInventoryService::class);

            foreach ($order->lines as $line) {
                $item = $line->item;
                if ($item === null) {
                    continue;
                }
                if (! $item->is_batch_tracked && ! $item->is_serial_tracked) {
                    continue;
                }

                $alloc = $allocations->get($line->id) ?? $allocations->get((string) $line->id);
                if (! is_array($alloc)) {
                    $v->errors()->add(
                        'lines',
                        "Batch or serial allocation required for {$item->sku}."
                    );

                    continue;
                }

                try {
                    $batchSerial->validateOutboundLine($item, $warehouseId, [
                        'batch_no' => $alloc['batch_no'] ?? null,
                        'serial_no' => $alloc['serial_no'] ?? null,
                        'quantity' => (string) $line->quantity,
                    ]);
                } catch (\InvalidArgumentException $e) {
                    $v->errors()->add('lines', $e->getMessage());
                }
            }
        });
    }
}
