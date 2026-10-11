<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Nomor dokumen berurutan per bulan, mis. SO-2610-0001. */
class Nomor
{
    public static function next(string $prefix, string $table, string $column = 'no'): string
    {
        $base = $prefix.'-'.now()->format('ym').'-';
        $last = DB::table($table)->where($column, 'like', $base.'%')->orderByDesc($column)->value($column);
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $base.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
