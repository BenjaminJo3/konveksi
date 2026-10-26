<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class MasterDataSpreadsheetReader
{
    public const MAX_DATA_ROWS = 5000;

    public function rows(UploadedFile $file): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'csv'
            ? $this->csvRows($file)
            : $this->xlsxRows($file);
    }

    private function csvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibaca.']);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return [];
        }
        rewind($handle);
        $delimiter = $this->csvDelimiter($firstLine);
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = $row;
            if (count($rows) > self::MAX_DATA_ROWS + 1) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per unggahan.']);
            }
        }

        fclose($handle);

        return $rows;
    }

    private function xlsxRows(UploadedFile $file): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'Komponen ZIP PHP diperlukan untuk membaca Excel (.xlsx).']);
        }

        $archive = new ZipArchive;
        if ($archive->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak valid atau rusak.']);
        }

        try {
            $workbookXml = $this->zipEntry($archive, 'xl/workbook.xml');
            $relationshipsXml = $this->zipEntry($archive, 'xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relationshipsXml === false) {
                throw ValidationException::withMessages(['file' => 'File Excel tidak memiliki struktur workbook yang valid.']);
            }
            $worksheetPath = $this->firstWorksheetPath($workbookXml, $relationshipsXml);
            $worksheet = $this->zipEntry($archive, $worksheetPath);
            $sharedStringsXml = $archive->locateName('xl/sharedStrings.xml') === false
                ? false
                : $this->zipEntry($archive, 'xl/sharedStrings.xml');
            if ($worksheet === false) {
                throw ValidationException::withMessages(['file' => 'Worksheet pertama pada workbook tidak dapat dibaca.']);
            }
            $sharedStrings = $sharedStringsXml === false ? [] : $this->sharedStrings($sharedStringsXml);

            return $this->worksheetRows($worksheet, $sharedStrings);
        } finally {
            $archive->close();
        }
    }

    private function firstWorksheetPath(string $workbookXml, string $relationshipsXml): string
    {
        $workbook = $this->parseXml($workbookXml);
        $firstSheet = $workbook->xpath('//*[local-name()="sheets"]/*[local-name()="sheet"]')[0] ?? null;
        if (! $firstSheet) {
            throw ValidationException::withMessages(['file' => 'Workbook Excel tidak memiliki worksheet.']);
        }
        $relationshipId = (string) ($firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
        $relationships = $this->parseXml($relationshipsXml);
        foreach ($relationships->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = str_replace('\\', '/', (string) $relationship['Target']);
            if (str_contains($target, '..')) {
                break;
            }

            return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
        }

        throw ValidationException::withMessages(['file' => 'Lokasi worksheet pertama pada workbook tidak valid.']);
    }

    private function zipEntry(ZipArchive $archive, string $path): string|false
    {
        $stat = $archive->statName($path);
        if ($stat === false) {
            return false;
        }
        if (($stat['size'] ?? 0) > 25 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Worksheet Excel terlalu besar untuk diproses.']);
        }

        return $archive->getFromName($path);
    }

    private function sharedStrings(string $xml): array
    {
        $root = $this->parseXml($xml);
        $strings = [];
        foreach ($root->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $value = '';
            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $value .= (string) $text;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function worksheetRows(string $xml, array $sharedStrings): array
    {
        $root = $this->parseXml($xml);
        $rows = [];
        foreach ($root->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $rowElement) {
            $cells = [];
            foreach ($rowElement->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^([A-Z]+)/', $reference, $matches);
                if (! isset($matches[1])) {
                    continue;
                }
                $columnIndex = $this->columnIndex($matches[1]);
                $type = (string) $cell['t'];

                if ($type === 'inlineStr') {
                    $text = '';
                    foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                        $text .= (string) $textNode;
                    }
                    $value = $text;
                } else {
                    $rawValue = (string) ($cell->xpath('./*[local-name()="v"]')[0] ?? '');
                    $value = $type === 's' ? ($sharedStrings[(int) $rawValue] ?? '') : $rawValue;
                }

                $cells[$columnIndex] = $value;
            }
            if ($cells !== []) {
                $rows[] = $cells;
                if (count($rows) > self::MAX_DATA_ROWS + 1) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per unggahan.']);
                }
            }
        }

        return array_map(function (array $row): array {
            if ($row === []) {
                return [];
            }
            $lastColumn = max(array_keys($row));

            return array_map(fn (int $column): string => (string) ($row[$column] ?? ''), range(0, $lastColumn));
        }, $rows);
    }

    private function parseXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
            if ($root === false) {
                throw ValidationException::withMessages(['file' => 'Isi XML pada file Excel tidak valid.']);
            }

            return $root;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function csvDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $counts = array_map(fn (string $delimiter): int => count(str_getcsv($line, $delimiter)), $candidates);
        $index = array_search(max($counts), $counts, true);

        return $candidates[$index];
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }
}
