<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class MasterProductName
{
    public function make(
        string $category,
        string $subCategory,
        string $size,
        string $color,
        ?string $fabric = null,
    ): string {
        $name = implode(' ', array_filter(
            array_map(
                static fn (?string $part): string => trim($part ?? ''),
                [$category, $subCategory, $size, $color, $fabric],
            ),
            static fn (string $part): bool => $part !== '',
        ));

        if (mb_strlen($name) > 160) {
            throw ValidationException::withMessages([
                'name' => 'Nama barang hasil gabungan melebihi batas 160 karakter. Periksa nilai atribut yang dipilih.',
            ]);
        }

        return $name;
    }
}
