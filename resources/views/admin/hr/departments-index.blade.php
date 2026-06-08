<x-layouts.app title="Departments">
    <h1 class="page-title fs-18 my-4">Departments</h1>
    <div class="card custom-card"><div class="card-body">
        <form id="deptForm" class="row g-2 mb-4">
            @csrf
            <div class="col-auto"><input name="code" class="form-control" placeholder="Code" required></div>
            <div class="col-auto"><input name="name" class="form-control" placeholder="Name" required></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Add</button></div>
        </form>
        <table class="table" id="deptTable"><thead><tr><th>Code</th><th>Name</th><th>Active</th></tr></thead><tbody></tbody></table>
    </div></div>
    @push('scripts')
        <script>
            window.deptDataUrl = @json(route('admin.hr.departments.data'));
            window.deptStoreUrl = @json(route('admin.hr.departments.store'));
        </script>
        <script src="{{ asset('assets/admin/js/admin/hr/departments.js') }}"></script>
    @endpush
</x-layouts.app>
