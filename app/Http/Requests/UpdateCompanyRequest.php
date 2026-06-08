<?php

namespace App\Http\Requests;

use App\Enums\DefaultTaxType;
use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->can('company.edit');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('gstin')) {
            $this->merge(['gstin' => strtoupper((string) $this->input('gstin'))]);
        }
        if ($this->has('pan')) {
            $this->merge(['pan' => strtoupper((string) $this->input('pan'))]);
        }
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
        }
        if ($this->has('po_approval_threshold') && $this->input('po_approval_threshold') === '') {
            $this->merge(['po_approval_threshold' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:3', 'max:255'],
            'legal_name' => ['required', 'string', 'min:2', 'max:255'],
            'gstin' => ['required', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['required', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'address_line1' => ['required', 'string', 'min:5', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'state_code' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/', Rule::in(array_keys(config('gst.state_codes', [])))],
            'pincode' => ['required', 'string', 'size:6', 'regex:/^[1-9][0-9]{5}$/'],
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'financial_year_start' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'po_prefix' => ['required', 'string', 'max:20'],
            'default_tax_type' => ['required', 'string', Rule::enum(DefaultTaxType::class)],
            'whatsapp_enabled' => ['required', 'boolean'],
            'einvoice_enabled' => ['required', 'boolean'],
            'eway_enabled' => ['required', 'boolean'],
            'po_approval_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'pan.regex' => 'Enter a valid 10-character PAN.',
            'pincode.regex' => 'Pincode must be 6 digits and cannot start with 0.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'state_code.in' => 'Select a valid GST state code.',
        ];
    }
}
