<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Company-wide payroll statutory rules (single row).
 *
 * @property bool $pf_enabled
 * @property string $pf_employee_rate
 * @property string $pf_employer_rate
 * @property string $pf_wage_ceiling
 * @property string $pf_max_monthly_contribution
 * @property bool $pf_allow_opt_in_above_ceiling
 * @property bool $esi_enabled
 * @property string $esi_gross_ceiling
 * @property string $esi_employee_rate
 * @property string $esi_employer_rate
 * @property bool $pt_enabled
 * @property string $pt_monthly_amount
 * @property string $pt_min_gross
 */
class PayrollSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'pf_enabled',
        'pf_employee_rate',
        'pf_employer_rate',
        'pf_wage_ceiling',
        'pf_max_monthly_contribution',
        'pf_allow_opt_in_above_ceiling',
        'esi_enabled',
        'esi_gross_ceiling',
        'esi_employee_rate',
        'esi_employer_rate',
        'pt_enabled',
        'pt_monthly_amount',
        'pt_min_gross',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pf_enabled' => 'boolean',
            'pf_allow_opt_in_above_ceiling' => 'boolean',
            'esi_enabled' => 'boolean',
            'pt_enabled' => 'boolean',
            'pf_employee_rate' => 'decimal:4',
            'pf_employer_rate' => 'decimal:4',
            'pf_wage_ceiling' => 'decimal:2',
            'pf_max_monthly_contribution' => 'decimal:2',
            'esi_gross_ceiling' => 'decimal:2',
            'esi_employee_rate' => 'decimal:4',
            'esi_employer_rate' => 'decimal:4',
            'pt_monthly_amount' => 'decimal:2',
            'pt_min_gross' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        $row = self::query()->orderBy('id')->first();
        if ($row !== null) {
            return $row;
        }

        return self::query()->create([
            'pf_enabled' => true,
            'pf_employee_rate' => 0.12,
            'pf_employer_rate' => 0.12,
            'pf_wage_ceiling' => 15000,
            'pf_max_monthly_contribution' => 1800,
            'pf_allow_opt_in_above_ceiling' => true,
            'esi_enabled' => true,
            'esi_gross_ceiling' => 21000,
            'esi_employee_rate' => 0.0075,
            'esi_employer_rate' => 0.0325,
            'pt_enabled' => true,
            'pt_monthly_amount' => 200,
            'pt_min_gross' => 10000,
        ]);
    }
}
