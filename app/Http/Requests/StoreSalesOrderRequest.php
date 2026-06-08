<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Services\CustomerCreditService;
use App\Services\GstCalculationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SalesOrder::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('warehouse_id') && $this->input('warehouse_id') === '') {
            $this->merge(['warehouse_id' => null]);
        }
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['item_id'])
                && isset($line['quantity']) && $line['quantity'] !== ''
                && isset($line['unit_price']) && $line['unit_price'] !== '')
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'order_date' => ['required', 'date'],
            'expected_dispatch' => ['nullable', 'date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'credit_limit_override' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $customerId = $this->input('customer_id');
            if (! $customerId || $v->errors()->isNotEmpty()) {
                return;
            }
            $customer = Customer::query()->find((int) $customerId);
            $company = Company::query()->orderBy('id')->first();
            if ($customer === null || $company === null) {
                return;
            }
            $gst = app(GstCalculationService::class);
            $total = '0.00';
            foreach ($this->input('lines', []) as $line) {
                if (! is_array($line) || empty($line['item_id'])) {
                    continue;
                }
                $item = Item::query()->find((int) $line['item_id']);
                if ($item === null) {
                    continue;
                }
                $qty = (string) ($line['quantity'] ?? '0');
                $unitPrice = (string) ($line['unit_price'] ?? '0');
                $taxable = $gst->lineTaxable($qty, $unitPrice);
                $split = $gst->splitLineTax($taxable, (string) $item->gst_rate, $customer->state_code, $company->state_code);
                $lineTotal = bcadd(bcadd(bcadd($split['taxable'], $split['cgst'], 2), $split['sgst'], 2), $split['igst'], 2);
                $total = bcadd($total, $lineTotal, 2);
            }
            try {
                app(CustomerCreditService::class)->assertWithinLimit(
                    $customer,
                    $total,
                    $this->user(),
                    $this->boolean('credit_limit_override')
                );
            } catch (\InvalidArgumentException $e) {
                $v->errors()->add('customer_id', $e->getMessage());
            }
        });
    }
}
