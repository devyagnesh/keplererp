<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.payment.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payment_method' => ['required', 'string', 'in:NEFT,RTGS,Cheque,Cash'],
            'utr_reference' => ['nullable', 'string', 'max:64'],
            'payment_date' => ['required', 'date'],
        ];
    }
}
