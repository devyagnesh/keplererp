<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Auto-creates staff login on employee onboarding (SRS UC 22.6).
 */
class EmployeeProvisioningService
{
    public function __construct(
        protected WhatsAppNotificationService $whatsapp
    ) {}

    /**
     * @return array{user: User, plain_password: string}
     */
    public function createLoginForEmployee(Employee $employee, ?string $email = null): array
    {
        if ($employee->user_id !== null) {
            throw new InvalidArgumentException('Employee already has a linked user account.');
        }

        $loginEmail = $email ?? $employee->email;
        if ($loginEmail === null || trim($loginEmail) === '') {
            throw new InvalidArgumentException('Email is required to create a login.');
        }

        $plainPassword = Str::password(12);
        $user = User::query()->create([
            'name' => $employee->name,
            'email' => $loginEmail,
            'password' => Hash::make($plainPassword),
            'employee_id' => $employee->id,
        ]);
        $user->assignRole('Staff');

        $employee->update(['user_id' => $user->id, 'email' => $loginEmail]);

        $this->whatsapp->notifyEmployeePortalCredentials($employee, $loginEmail, $plainPassword);

        return ['user' => $user, 'plain_password' => $plainPassword];
    }
}
