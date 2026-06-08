<x-layouts.app title="Chart of accounts">
    <h1 class="page-title fs-18 my-4">Chart of accounts</h1>
    <div class="card custom-card"><div class="card-body">
        <form id="coaForm" class="row g-2 mb-4">
            @csrf
            <div class="col-md-2"><input name="account_code" class="form-control" placeholder="Code" required></div>
            <div class="col-md-3"><input name="account_name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-2">
                <select name="account_type" class="form-select" required>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="revenue">Revenue</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-primary">Add account</button></div>
        </form>
        <table class="table" id="coaTable"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>System</th></tr></thead><tbody></tbody></table>
    </div></div>
    @push('scripts')
        <script>
            window.coaDataUrl = @json(route('admin.finance.chart-of-accounts.data'));
            window.coaStoreUrl = @json(route('admin.finance.chart-of-accounts.store'));
        </script>
        <script src="{{ asset('assets/admin/js/admin/finance/chart-of-accounts.js') }}"></script>
    @endpush
</x-layouts.app>
