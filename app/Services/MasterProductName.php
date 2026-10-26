<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class MasterProductName
{
    public function make(string $subCategory, string $color, string $article, string $size): string
    {
        $name = implode(' ', array_map(
            static fn (string $part): string => trim($part),
            [$subCategory, $color, $article, $size],
        ));

        if (mb_strlen($name) > 160) {
            throw ValidationException::withMessages([
                'name' => 'Nama barang hasil gabungan melebihi batas 160 karakter. Periksa nilai atribut yang dipilih.',
            ]);
        }

        return $name;
    }
}
