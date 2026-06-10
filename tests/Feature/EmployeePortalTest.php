<?php

namespace Tests\Feature;

use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    private function linkedEmployeeUser(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Employee');
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-PORTAL',
            'name' => 'Portal Employee',
            'user_id' => $user->id,
            'is_active' => true,
            'basic_salary' => '15000.00',
            'department' => 'Production',
            'designation' => 'Operator',
        ]);

        return [$user, $employee];
    }

    public function test_employee_dashboard_requires_linked_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $this->actingAs($user)
            ->get(route('employee.dashboard'))
            ->assertForbidden();
    }

    public function test_employee_can_access_dashboard_and_portal_pages(): void
    {
        [$user, $employee] = $this->linkedEmployeeUser();

        $this->actingAs($user)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Employee dashboard', false)
            ->assertSee($employee->emp_code, false);

        $this->actingAs($user)
            ->get(route('employee.attendance.index'))
            ->assertOk()
            ->assertSee('My attendance', false);

        $this->actingAs($user)
            ->get(route('employee.leave.index'))
            ->assertOk()
            ->assertSee('Apply for leave', false);

        $this->actingAs($user)
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertSee('My payslips', false);

        $this->actingAs($user)
            ->get(route('employee.profile.show'))
            ->assertOk()
            ->assertSee('Employment details', false);
    }

    public function test_employee_login_redirects_to_dashboard(): void
    {
        [$user] = $this->linkedEmployeeUser();

        $this->actingAs($user)
            ->get(route('admin.home'))
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_employee_can_submit_leave_application(): void
    {
        [$user, $employee] = $this->linkedEmployeeUser();

        $this->actingAs($user)
            ->postJson(route('employee.leave.store'), [
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'leave_type' => 'CASUAL',
                'reason' => 'Family function',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Leave application submitted successfully.');

        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employee->id,
            'leave_type' => 'CASUAL',
            'status' => 'pending',
        ]);
    }

    public function test_employee_attendance_data_is_scoped_to_self(): void
    {
        [$user, $employee] = $this->linkedEmployeeUser();
        $other = Employee::query()->create([
            'emp_code' => 'EMP-OTHER',
            'name' => 'Other Staff',
            'is_active' => true,
            'basic_salary' => '10000.00',
        ]);

        AttendanceEntry::query()->create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
        ]);
        AttendanceEntry::query()->create([
            'employee_id' => $other->id,
            'work_date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('employee.attendance.data'), [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'month' => now()->format('Y-m'),
            ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('present', $data[0]['status']);
    }

    public function test_employee_leave_data_is_scoped_to_self(): void
    {
        [$user, $employee] = $this->linkedEmployeeUser();
        $other = Employee::query()->create([
            'emp_code' => 'EMP-OTHER2',
            'name' => 'Other Staff',
            'is_active' => true,
            'basic_salary' => '10000.00',
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'leave_type' => 'SICK',
            'status' => 'pending',
        ]);
        LeaveApplication::query()->create([
            'employee_id' => $other->id,
            'start_date' => now()->addWeeks(2)->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'leave_type' => 'CASUAL',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('employee.leave.data'), [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]);

        $response->assertOk();
        $this->assertSame(1, (int) $response->json('recordsTotal'));
    }
}
