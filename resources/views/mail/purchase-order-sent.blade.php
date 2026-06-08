<x-mail::message>
# Purchase order {{ $poNumber }}

Dear {{ $vendorName }},

Purchase order **{{ $poNumber }}** has been issued. Total value: **₹{{ $total }}**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
