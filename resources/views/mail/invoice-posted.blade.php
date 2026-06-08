<x-mail::message>
# Invoice {{ $invoiceNumber }}

Your invoice **{{ $invoiceNumber }}** for **₹{{ $total }}** is posted.

Payment due by **{{ $dueDate }}**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
