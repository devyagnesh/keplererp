<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sequential document number generator shared outside controllers.
 */
class DocumentNumberService
{
    public function next(string $table, string $prefix): string
    {
        $max = (int) DB::table($table)->max('id');

        return $prefix.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
