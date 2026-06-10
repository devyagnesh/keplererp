<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates high-accuracy GPS payload for employee check-in / check-out.
 */
class EmployeeAttendanceCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.attendance.self_mark') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['required', 'numeric', 'min:0', 'max:1000'],
            'altitude_m' => ['nullable', 'numeric', 'between:-500,9000'],
            'altitude_accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed_m_s' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'captured_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accuracy_m.max' => 'GPS accuracy reading is invalid.',
            'latitude.between' => 'Latitude is out of range.',
            'longitude.between' => 'Longitude is out of range.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $maxAccuracy = (float) config('attendance.max_accuracy_m', 150);
            $accuracy = (float) $this->input('accuracy_m');
            if ($accuracy > $maxAccuracy) {
                $v->errors()->add(
                    'accuracy_m',
                    sprintf('GPS accuracy (±%.0f m) is too low. Move outdoors or enable precise location, then try again.', $accuracy)
                );
            }

            $maxAge = (int) config('attendance.max_capture_age_seconds', 120);
            $capturedAt = $this->date('captured_at');
            if ($capturedAt !== null && $capturedAt->lt(now()->subSeconds($maxAge))) {
                $v->errors()->add('captured_at', 'Location fix is stale. Capture a fresh GPS reading.');
            }
        });
    }

    /**
     * Normalised geolocation payload for persistence.
     *
     * @return array<string, mixed>
     */
    public function geolocationPayload(): array
    {
        $v = $this->validated();

        return [
            'latitude' => round((float) $v['latitude'], 8),
            'longitude' => round((float) $v['longitude'], 8),
            'accuracy_m' => round((float) $v['accuracy_m'], 3),
            'altitude_m' => isset($v['altitude_m']) ? round((float) $v['altitude_m'], 3) : null,
            'altitude_accuracy_m' => isset($v['altitude_accuracy_m']) ? round((float) $v['altitude_accuracy_m'], 3) : null,
            'heading' => isset($v['heading']) ? round((float) $v['heading'], 2) : null,
            'speed_m_s' => isset($v['speed_m_s']) ? round((float) $v['speed_m_s'], 3) : null,
            'captured_at' => $this->date('captured_at')?->toIso8601String(),
            'user_agent' => substr((string) $this->userAgent(), 0, 255),
        ];
    }
}
