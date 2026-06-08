<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->route('purchase_order');

        return $this->user()?->can('reject', $purchaseOrder) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejected_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
