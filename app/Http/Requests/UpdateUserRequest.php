<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $model = $this->route('user');

        return $model instanceof User
            && $this->user() !== null
            && $this->user()->can('update', $model);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'is_active' => ['required', 'boolean'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'whatsapp_number.regex' => 'Enter a valid 10-digit WhatsApp number.',
            'role_id.required' => 'Select a role for this user.',
            'role_id.exists' => 'The selected role is invalid.',
        ];
    }
}
