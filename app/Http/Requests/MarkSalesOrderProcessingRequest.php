<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkSalesOrderProcessingRequest extends FormRequest
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
            'courier_name' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'transporter_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
