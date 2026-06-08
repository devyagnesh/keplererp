<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $rows = $customer->addresses()
            ->orderByDesc('is_default_shipping')
            ->get(['id', 'label', 'address_line1', 'city', 'state_code', 'pincode', 'is_default_shipping']);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state_code' => ['required', 'string', 'size:2'],
            'pincode' => ['required', 'string', 'size:6'],
            'is_default_shipping' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_default_shipping')) {
            CustomerAddress::query()->where('customer_id', $customer->id)->update(['is_default_shipping' => false]);
        }

        $address = $customer->addresses()->create([
            'label' => $data['label'] ?? 'Ship To',
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'state_code' => $data['state_code'],
            'pincode' => $data['pincode'],
            'is_default_shipping' => $request->boolean('is_default_shipping'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Shipping address saved.',
            'data' => ['id' => $address->id],
        ], 201);
    }
}
