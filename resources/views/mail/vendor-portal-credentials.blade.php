<x-mail::message>
# Vendor portal access

Hello {{ $vendorName }},

Your vendor portal account ({{ $vendorCode }}) is ready.

**Portal URL:** [{{ $portalUrl }}]({{ $portalUrl }})

**Temporary password:** `{{ $plainPassword }}`

Please sign in and change your password immediately. Do not share these credentials.

<x-mail::button :url="$portalUrl">
Open vendor portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
