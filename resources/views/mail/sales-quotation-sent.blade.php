<x-mail::message>
# Quotation {{ $quoteNumber }}

Dear {{ $customerName }},

Please find your quotation **{{ $quoteNumber }}**. Valid until **{{ $validUntil }}**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
