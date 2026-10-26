<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MasterDataCodeGenerator
{
    public function generate(string $prefix, string $table): string
    {
        do {
            $code = $prefix.'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (DB::table($table)->where('code', $code)->exists());

        return $code;
    }
}
