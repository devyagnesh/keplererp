<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()?->can('customers.edit'), 403);

        return view('admin.sales.price-lists-index', [
            'priceLists' => PriceList::query()->orderBy('code')->get(),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('customers.edit'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:price_lists,code'],
            'name' => ['required', 'string', 'max:120'],
        ]);
        PriceList::query()->create(array_merge($data, ['is_active' => true]));

        return response()->json(['status' => true, 'message' => 'Price list created.'], 201);
    }

    public function storeItem(Request $request, PriceList $priceList): JsonResponse
    {
        abort_unless($request->user()?->can('customers.edit'), 403);
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        PriceListItem::query()->updateOrCreate(
            ['price_list_id' => $priceList->id, 'item_id' => $data['item_id']],
            ['unit_price' => $data['unit_price']]
        );

        return response()->json(['status' => true, 'message' => 'Price list item saved.']);
    }

    public function items(PriceList $priceList): JsonResponse
    {
        abort_unless(request()->user()?->can('customers.view'), 403);

        $rows = $priceList->items()->with('item:id,sku,name')->get()->map(fn (PriceListItem $row) => [
            'item_id' => $row->item_id,
            'item_label' => $row->item?->display_label ?? '—',
            'unit_price' => (string) $row->unit_price,
        ]);

        return response()->json(['data' => $rows]);
    }
}
