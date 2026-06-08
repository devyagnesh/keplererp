<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstPeriodLock extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['period_year', 'period_month', 'locked_by', 'locked_at'];

    protected function casts(): array
    {
        return ['locked_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
