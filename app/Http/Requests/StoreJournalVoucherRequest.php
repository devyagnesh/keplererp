<?php

namespace App\Http\Requests;

use App\Models\JournalVoucher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJournalVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JournalVoucher::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['account_code'])
                && (isset($line['debit']) || isset($line['credit'])))
            ->values()
            ->all();
        $this->merge(['lines' => $lines]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'voucher_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:2', 'max:40'],
            'lines.*.account_code' => ['required', 'string', 'max:32'],
            'lines.*.account_name' => ['nullable', 'string', 'max:120'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $debit = '0';
            $credit = '0';
            foreach ($this->input('lines', []) as $index => $line) {
                $d = (string) ($line['debit'] ?? '0');
                $c = (string) ($line['credit'] ?? '0');
                if (bccomp($d, '0', 2) > 0 && bccomp($c, '0', 2) > 0) {
                    $v->errors()->add('lines.'.$index.'.debit', 'Enter either debit or credit, not both.');
                }
                $debit = bcadd($debit, $d, 2);
                $credit = bcadd($credit, $c, 2);
            }
            if (bccomp($debit, $credit, 2) !== 0) {
                $v->errors()->add('lines', 'Total debit must equal total credit.');
            }
        });
    }
}
