<?php

namespace App\Http\Requests\VendorPortal;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('vendor')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vendor_payable_id' => ['required', 'integer', 'exists:vendor_payables,id'],
            'vendor_invoice_number' => ['required', 'string', 'max:50'],
            'invoice_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
