<x-layouts.app title="Price lists">
    <h1 class="page-title fs-18 my-4">Price lists</h1>
    <div class="row">
        <div class="col-md-4">
            <div class="card custom-card"><div class="card-body">
                <form id="plForm" action="{{ route('admin.sales.price-lists.store') }}">
                    @csrf
                    <div class="mb-2"><input name="code" class="form-control" placeholder="Code" required></div>
                    <div class="mb-2"><input name="name" class="form-control" placeholder="Name" required></div>
                    <button class="btn btn-primary">Create list</button>
                </form>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card custom-card"><div class="card-body">
                <form id="pliForm" class="row g-2">
                    <div class="col-md-4">
                        <select id="plSelect" class="form-select">
                            @foreach ($priceLists as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->code }} — {{ $pl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="item_id" class="form-select">
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input name="unit_price" type="number" step="0.0001" class="form-control" placeholder="Price" required></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-outline-primary">Add</button></div>
                </form>
                <table class="table mt-3" id="pliTable"><thead><tr><th colspan="2">Item</th><th>Price</th></tr></thead><tbody></tbody></table>
            </div></div>
        </div>
    </div>
    @push('scripts')
        <script>window.plItemUrlBase = @json(url('/admin/sales/price-lists'));</script>
        <script src="{{ asset('assets/admin/js/admin/sales/price-lists.js') }}"></script>
    @endpush
</x-layouts.app>
