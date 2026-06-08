<x-layouts.app title="Allowance types">
    <h1 class="page-title fs-18 my-4">Allowance types</h1>
    <p class="text-muted fs-13">Define earning components (HRA, conveyance, etc.). Assign monthly amounts per employee on the employee form.</p>
    <div class="card custom-card">
        <div class="card-body">
            <form id="allowanceTypeForm" class="row g-2 mb-4">
                @csrf
                <div class="col-auto"><input name="code" class="form-control" placeholder="Code" required maxlength="32"></div>
                <div class="col-auto"><input name="name" class="form-control" placeholder="Name" required maxlength="100"></div>
                <div class="col-auto"><input name="sort_order" type="number" class="form-control" placeholder="Order" value="0"></div>
                <div class="col-auto form-check pt-2">
                    <input class="form-check-input" type="checkbox" name="included_in_esi_gross" value="1" id="esiGross" checked>
                    <label class="form-check-label" for="esiGross">In ESI gross</label>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Add</button></div>
            </form>
            <table class="table table-bordered" id="allowanceTypeTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Order</th>
                        <th>ESI gross</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    @push('scripts')
        <script>
            window.allowanceTypeDataUrl = @json(route('admin.hr.allowance-types.data'));
            window.allowanceTypeStoreUrl = @json(route('admin.hr.allowance-types.store'));
            window.allowanceTypeUpdateUrl = @json(url('/admin/hr/allowance-types/__ID__'));
        </script>
        <script src="{{ asset('js/modules/erp/allowance-types.js') }}"></script>
    @endpush
</x-layouts.app>
