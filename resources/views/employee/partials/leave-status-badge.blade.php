@php
    $badgeClass = match ($status) {
        'approved' => 'bg-success-transparent',
        'rejected' => 'bg-danger-transparent',
        'pending' => 'bg-warning-transparent',
        default => 'bg-secondary-transparent',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ ucfirst((string) $status) }}</span>
