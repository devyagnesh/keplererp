@php
    $badgeClass = match ($status) {
        'present' => 'bg-success-transparent',
        'absent' => 'bg-danger-transparent',
        'half' => 'bg-warning-transparent',
        'leave' => 'bg-info-transparent',
        default => 'bg-secondary-transparent',
    };
    $label = match ($status) {
        'present' => 'Present',
        'absent' => 'Absent',
        'half' => 'Half day',
        'leave' => 'Leave',
        default => ucfirst((string) $status),
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $label }}</span>
