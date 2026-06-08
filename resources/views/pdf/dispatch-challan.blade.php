<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dispatch Challan {{ $challan->challan_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <strong>{{ $company?->legal_name ?? 'Company' }}</strong>
    <h2>Dispatch Challan</h2>
    <p>Challan {{ $challan->challan_number }} · SO {{ $order?->order_number }} · {{ $challan->dispatched_at?->format('d M Y H:i') }}</p>
    <p>Ship to: {{ $customer?->name }}</p>
    @if ($challan->eway_bill_no)
        <p><strong>e-Way Bill:</strong> {{ $challan->eway_bill_no }}</p>
        @if ($challan->eway_qr)
            <p style="font-size: 10px;">{{ $challan->eway_qr }}</p>
        @endif
    @endif
    <table>
        <thead>
            <tr><th>SKU</th><th>Item</th><th>Qty</th></tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $line->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
