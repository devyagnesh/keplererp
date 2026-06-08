<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\DB;

trait IssuesDocumentNumbers
{
    /**
     * Next sequential document code (table max id + 1).
     */
    protected function nextDocumentCode(string $table, string $prefix): string
    {
        $max = (int) DB::table($table)->max('id');

        return $prefix.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
