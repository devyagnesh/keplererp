<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstPeriodLock extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'period_year',
        'period_month',
        'locked_by',
        'locked_at',
        'gstr1_arn',
        'gstr1_filed_at',
        'gstr3b_arn',
        'gstr3b_filed_at',
        'gstr3b_tax_paid',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'gstr1_filed_at' => 'datetime',
            'gstr3b_filed_at' => 'datetime',
            'gstr3b_tax_paid' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
