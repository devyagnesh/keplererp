<?php

namespace App\Http\Requests;

use App\Models\AttendanceEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AttendanceEntry::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:present,absent,leave,half'],
        ];
    }
}
