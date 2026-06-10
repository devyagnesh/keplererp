<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Self-service leave application for the logged-in employee.
 */
class EmployeeStoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.leave.apply') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'string', 'in:CASUAL,SICK,EARNED,UNPAID'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot start in the past.',
            'leave_type.in' => 'Please select a valid leave type.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $employee = $this->linkedEmployee();
            if ($employee === null) {
                $v->errors()->add('start_date', 'No employee profile is linked to your account.');

                return;
            }

            $start = $this->input('start_date');
            $end = $this->input('end_date');
            $overlap = LeaveApplication::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_date', '<=', $end)
                ->where('end_date', '>=', $start)
                ->exists();

            if ($overlap) {
                $v->errors()->add('start_date', 'Leave dates overlap an existing application.');
            }
        });
    }

    public function linkedEmployee(): ?Employee
    {
        $user = $this->user();
        if ($user === null) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }
}
