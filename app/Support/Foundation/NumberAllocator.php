<?php

namespace App\Support\Foundation;

use Illuminate\Support\Facades\DB;
use OverflowException;
use RuntimeException;

class NumberAllocator
{
    public function next(string $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            $counter = DB::table('MSNumberSequence')->where('NSPrefix', $prefix)->lockForUpdate()->first();
            if (! $counter) {
                throw new RuntimeException('Foundation number prefix has not been registered.');
            }
            $next = $counter->NSLastValue + 1;
            if ($next > 99999999) {
                throw new OverflowException('Foundation number range is exhausted.');
            }
            DB::table('MSNumberSequence')->where('NSNo', $counter->NSNo)->update(['NSLastValue' => $next, 'NSMD' => now()]);

            return $prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
        });
    }
}
