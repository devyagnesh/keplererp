<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
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
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'address_line1' => ['required', 'string', 'min:5', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'state_code' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/', Rule::in(array_keys(config('gst.state_codes', [])))],
            'pincode' => ['required', 'string', 'size:6', 'regex:/^[1-9][0-9]{5}$/'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'price_list_id' => ['nullable', 'integer', 'exists:price_lists,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'pan.regex' => 'Enter a valid 10-character PAN.',
            'pincode.regex' => 'Pincode must be 6 digits and cannot start with 0.',
            'state_code.in' => 'Select a valid GST state code.',
        ];
    }
}
