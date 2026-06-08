<x-layouts.app title="Edit employee">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Edit employee</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hr.employees.index') }}">Employees</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body">
                    <form id="employeeForm" method="POST" action="{{ route('admin.hr.employees.update', $employee) }}"
                        novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Employee code</label>
                            <input type="text" name="emp_code" class="form-control" value="{{ $employee->emp_code }}"
                                required maxlength="32">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $employee->name }}" required
                                maxlength="120">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $employee->email }}"
                                maxlength="191">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $employee->phone }}"
                                maxlength="20">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}" @selected($employee->department_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation</label>
                            <select name="designation_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($designations as $d)
                                    <option value="{{ $d->id }}" @selected($employee->designation_id == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Join date</label>
                            <input type="date" name="join_date" class="form-control"
                                value="{{ $employee->join_date?->format('Y-m-d') }}">
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="empActive"
                                {{ $employee->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="empActive">Active</label>
                        </div>
                        @include('admin.hr._employee-payroll-fields', ['employee' => $employee])
                        <button type="submit" class="btn btn-primary" id="employeeSubmit">Save</button>
                        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.employeeFormSubmitUrl = @json(route('admin.hr.employees.update', $employee));
            window.employeeFormMethod = 'PUT';
            window.employeesIndexUrl = @json(route('admin.hr.employees.index'));
        </script>
        <script src="{{ asset('js/modules/erp/employee-form.js') }}"></script>
    @endpush
</x-layouts.app>
