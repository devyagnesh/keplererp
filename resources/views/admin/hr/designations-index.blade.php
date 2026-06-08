<x-layouts.app title="Designations">
    <h1 class="page-title fs-18 my-4">Designations</h1>
    <div class="card custom-card"><div class="card-body">
        <form id="desigForm" class="row g-2 mb-4">
            @csrf
            <div class="col-auto"><input name="code" class="form-control" placeholder="Code" required></div>
            <div class="col-auto"><input name="name" class="form-control" placeholder="Name" required></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Add</button></div>
        </form>
        <table class="table" id="desigTable"><thead><tr><th>Code</th><th>Name</th></tr></thead><tbody></tbody></table>
    </div></div>
    @push('scripts')
        <script>
            window.desigDataUrl = @json(route('admin.hr.designations.data'));
            window.desigStoreUrl = @json(route('admin.hr.designations.store'));
        </script>
        <script src="{{ asset('assets/admin/js/admin/hr/designations.js') }}"></script>
    @endpush
</x-layouts.app>
