<x-mail::message>
# Payslip

Dear {{ $employeeName }},

Your salary of **₹{{ $netSalary }}** has been processed. Your payslip is attached to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
