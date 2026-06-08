<?php

namespace App\Services;

use App\Models\AllowanceType;
use App\Models\Employee;
use App\Models\PayrollSetting;
use InvalidArgumentException;

/**
 * Applies configurable payroll rules (PF, ESI, PT) and allowance totals.
 */
class PayrollCalculationService
{
    /**
     * @return array{pf_employee: string, pf_employer: string}
     */
    public function calculatePf(Employee $employee, string $pfBase, PayrollSetting $settings): array
    {
        if (! $settings->pf_enabled) {
            return ['pf_employee' => '0.00', 'pf_employer' => '0.00'];
        }

        if ($employee->pf_number === null || trim((string) $employee->pf_number) === '') {
            return ['pf_employee' => '0.00', 'pf_employer' => '0.00'];
        }

        $ceiling = (string) $settings->pf_wage_ceiling;
        $aboveCeiling = bccomp($pfBase, $ceiling, 2) > 0;

        if ($aboveCeiling) {
            if (! $settings->pf_allow_opt_in_above_ceiling || ! $employee->pf_opted_in) {
                return ['pf_employee' => '0.00', 'pf_employer' => '0.00'];
            }
        } elseif (! $employee->pf_opted_in) {
            return ['pf_employee' => '0.00', 'pf_employer' => '0.00'];
        }

        $wageForPf = $aboveCeiling ? $ceiling : $pfBase;
        $pfEmployee = bcmul($wageForPf, (string) $settings->pf_employee_rate, 2);
        $pfEmployer = bcmul($wageForPf, (string) $settings->pf_employer_rate, 2);
        $cap = (string) $settings->pf_max_monthly_contribution;

        if (bccomp($cap, '0', 2) > 0) {
            if (bccomp($pfEmployee, $cap, 2) > 0) {
                $pfEmployee = $cap;
            }
            if (bccomp($pfEmployer, $cap, 2) > 0) {
                $pfEmployer = $cap;
            }
        }

        return ['pf_employee' => $pfEmployee, 'pf_employer' => $pfEmployer];
    }

    /**
     * @return array{esi_employee: string, esi_employer: string, esi_gross: string}
     */
    public function calculateEsi(Employee $employee, string $grossForEsi, PayrollSetting $settings): array
    {
        if (! $settings->esi_enabled) {
            return ['esi_employee' => '0.00', 'esi_employer' => '0.00', 'esi_gross' => $grossForEsi];
        }

        if ($employee->esi_number === null || trim((string) $employee->esi_number) === '') {
            return ['esi_employee' => '0.00', 'esi_employer' => '0.00', 'esi_gross' => $grossForEsi];
        }

        if (bccomp($grossForEsi, (string) $settings->esi_gross_ceiling, 2) > 0) {
            return ['esi_employee' => '0.00', 'esi_employer' => '0.00', 'esi_gross' => $grossForEsi];
        }

        return [
            'esi_employee' => bcmul($grossForEsi, (string) $settings->esi_employee_rate, 2),
            'esi_employer' => bcmul($grossForEsi, (string) $settings->esi_employer_rate, 2),
            'esi_gross' => $grossForEsi,
        ];
    }

    public function calculateProfessionalTax(string $gross, PayrollSetting $settings): string
    {
        if (! $settings->pt_enabled || bccomp($gross, '0', 2) <= 0) {
            return '0.00';
        }

        if (bccomp($gross, (string) $settings->pt_min_gross, 2) < 0) {
            return '0.00';
        }

        return (string) $settings->pt_monthly_amount;
    }

    /**
     * Sum configured allowances for an employee (loaded with allowanceType).
     *
     * @return array{total: string, lines: list<array{code: string, name: string, amount: string}>, by_code: array<string, string>}
     */
    public function sumAllowances(Employee $employee): array
    {
        $total = '0.00';
        $lines = [];
        $byCode = [];

        foreach ($employee->employeeAllowances as $row) {
            $type = $row->allowanceType;
            if ($type === null || ! $type->is_active) {
                continue;
            }
            $amount = bcadd((string) $row->monthly_amount, '0', 2);
            if (bccomp($amount, '0', 2) <= 0) {
                continue;
            }
            $total = bcadd($total, $amount, 2);
            $code = strtoupper($type->code);
            $byCode[$code] = $amount;
            $lines[] = [
                'code' => $code,
                'name' => $type->name,
                'amount' => $amount,
            ];
        }

        usort($lines, fn (array $a, array $b): int => strcmp($a['code'], $b['code']));

        return ['total' => $total, 'lines' => $lines, 'by_code' => $byCode];
    }

    /**
     * Gross for ESI = adjusted basic + allowances flagged included_in_esi_gross.
     */
    public function grossForEsi(string $adjustedBasic, Employee $employee): string
    {
        $total = $adjustedBasic;
        foreach ($employee->employeeAllowances as $row) {
            $type = $row->allowanceType;
            if ($type === null || ! $type->is_active || ! $type->included_in_esi_gross) {
                continue;
            }
            $total = bcadd($total, (string) $row->monthly_amount, 2);
        }

        return $total;
    }

    /**
     * @param  list<array{code: string, name: string, amount: string}>  $allowanceLines
     * @return array<string, mixed>
     */
    public function buildEarningsBreakdown(string $adjustedBasic, array $allowanceLines): array
    {
        return [
            'basic' => $adjustedBasic,
            'allowances' => $allowanceLines,
        ];
    }

    /**
     * Map legacy payroll_detail columns from allowance codes.
     *
     * @param  array<string, string>  $byCode
     * @return array{hra: string, conveyance: string}
     */
    public function legacyAllowanceColumns(array $byCode): array
    {
        return [
            'hra' => $byCode['HRA'] ?? '0.00',
            'conveyance' => $byCode['CONVEYANCE'] ?? '0.00',
        ];
    }

    /**
     * @param  list<array{allowance_type_id: int, monthly_amount: string|float|int}>  $rows
     */
    public function syncEmployeeAllowances(Employee $employee, array $rows): void
    {
        $employee->employeeAllowances()->delete();

        foreach ($rows as $row) {
            if (! isset($row['allowance_type_id'], $row['monthly_amount'])) {
                continue;
            }
            $amount = bcadd((string) $row['monthly_amount'], '0', 2);
            if (bccomp($amount, '0', 2) <= 0) {
                continue;
            }
            $typeId = (int) $row['allowance_type_id'];
            if (! AllowanceType::query()->where('id', $typeId)->where('is_active', true)->exists()) {
                throw new InvalidArgumentException('Invalid or inactive allowance type.');
            }
            $employee->employeeAllowances()->create([
                'allowance_type_id' => $typeId,
                'monthly_amount' => $amount,
            ]);
        }
    }
}
