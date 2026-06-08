<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle ?? 'Document' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px 32px 48px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .no-border td, .no-border th { border: none; padding: 2px 0; }
        .header-table { margin-bottom: 12px; }
        .doc-title { text-align: center; font-size: 16px; font-weight: bold; margin: 8px 0 14px; letter-spacing: 1px; }
        .muted { color: #666; font-size: 10px; }
        .section { margin-top: 12px; }
        .totals { width: 42%; margin-left: auto; margin-top: 12px; }
        .watermark {
            position: fixed; top: 38%; left: 8%; width: 84%; text-align: center;
            font-size: 56px; font-weight: bold; color: rgba(220, 53, 69, 0.18);
            transform: rotate(-35deg); z-index: -1;
        }
        .footer {
            position: fixed; bottom: 16px; left: 32px; right: 32px;
            border-top: 1px solid #ddd; padding-top: 6px; font-size: 9px; color: #666;
        }
    </style>
</head>
<body>
    @if (!empty($watermark))
        <div class="watermark">{{ $watermark }}</div>
    @endif

    <table class="header-table no-border">
        <tr>
            <td style="width: 55%;">
                <strong style="font-size: 14px;">{{ $company?->legal_name ?? $company?->company_name ?? 'Company' }}</strong><br>
                @if ($company)
                    GSTIN: {{ $company->gstin }}<br>
                    {{ $company->address_line1 }}@if($company->address_line2), {{ $company->address_line2 }}@endif<br>
                    {{ $company->city }} — {{ $company->pincode }} ({{ $company->state_code }})<br>
                    {{ $company->phone }} · {{ $company->email }}
                @endif
            </td>
            <td style="width: 45%; text-align: right;">
                @if ($company?->logo)
                    <img src="{{ public_path('storage/'.$company->logo) }}" alt="Logo" style="max-height: 48px;">
                @endif
            </td>
        </tr>
    </table>

    <div class="doc-title">{{ $docTitle ?? '' }}</div>

    @yield('content')

    <div class="footer">
        This is a computer-generated document. Generated at {{ $generatedAt ?? now()->format('d M Y H:i') }}.
    </div>
</body>
</html>
