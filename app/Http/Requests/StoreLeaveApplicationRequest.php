<?php

namespace App\Http\Requests;

use App\Models\LeaveApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.employee.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'string', 'in:CASUAL,SICK,EARNED,UNPAID'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $employeeId = (int) $this->input('employee_id');
            $start = $this->input('start_date');
            $end = $this->input('end_date');
            $overlap = LeaveApplication::query()
                ->where('employee_id', $employeeId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_date', '<=', $end)
                ->where('end_date', '>=', $start)
                ->exists();
            if ($overlap) {
                $v->errors()->add('start_date', 'Leave dates overlap an existing application.');
            }
        });
    }
}
