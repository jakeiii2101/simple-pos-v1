<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class ProductImportReader
{
    public const MAX_ROWS = 2000;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        $rows = match ($extension) {
            'csv' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw ValidationException::withMessages([
                'importFile' => 'Only CSV and XLSX files are supported.',
            ]),
        };

        if ($rows === []) {
            throw ValidationException::withMessages([
                'importFile' => 'The import file is empty or does not contain a header row.',
            ]);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'importFile' => 'A maximum of '.self::MAX_ROWS.' product rows may be imported at once.',
            ]);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'importFile' => 'The CSV file could not be opened.',
            ]);
        }

        try {
            $rawRows = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rawRows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $this->rowsToAssociative($rawRows);
    }

    /**
     * Read the first worksheet from a standard .xlsx workbook without requiring
     * an additional spreadsheet package.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'importFile' => 'XLSX import is not available because the PHP ZIP extension is missing.',
            ]);
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path);

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'importFile' => 'The XLSX file could not be opened.',
            ]);
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw ValidationException::withMessages([
                    'importFile' => 'The XLSX workbook does not contain a readable first worksheet.',
                ]);
            }

            $document = new \DOMDocument;

            if (! @$document->loadXML($sheetXml)) {
                throw ValidationException::withMessages([
                    'importFile' => 'The XLSX worksheet is invalid.',
                ]);
            }

            $rawRows = [];

            foreach ($document->getElementsByTagName('row') as $rowNode) {
                $valuesByColumn = [];
                $maxColumn = -1;

                foreach ($rowNode->childNodes as $cellNode) {
                    if (! $cellNode instanceof \DOMElement || $cellNode->localName !== 'c') {
                        continue;
                    }

                    $reference = $cellNode->getAttribute('r');
                    $letters = preg_replace('/[^A-Z]/i', '', $reference) ?: '';
                    $column = $this->columnIndex($letters);

                    if ($column < 0) {
                        continue;
                    }

                    $valuesByColumn[$column] = $this->readCellValue($cellNode, $sharedStrings);
                    $maxColumn = max($maxColumn, $column);
                }

                if ($maxColumn < 0) {
                    $rawRows[] = [];
                    continue;
                }

                $row = [];

                for ($column = 0; $column <= $maxColumn; $column++) {
                    $row[] = $valuesByColumn[$column] ?? '';
                }

                $rawRows[] = $row;
            }

            return $this->rowsToAssociative($rawRows);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    protected function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = new \DOMDocument;

        if (! @$document->loadXML($xml)) {
            return [];
        }

        $strings = [];

        foreach ($document->getElementsByTagName('si') as $item) {
            $value = '';

            foreach ($item->getElementsByTagName('t') as $textNode) {
                $value .= $textNode->textContent;
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    protected function readCellValue(\DOMElement $cell, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            $value = '';

            foreach ($cell->getElementsByTagName('t') as $textNode) {
                $value .= $textNode->textContent;
            }

            return trim($value);
        }

        $valueNode = $cell->getElementsByTagName('v')->item(0);
        $raw = $valueNode?->textContent ?? '';

        if ($type === 's') {
            return trim($sharedStrings[(int) $raw] ?? '');
        }

        return trim($raw);
    }

    protected function columnIndex(string $letters): int
    {
        if ($letters === '') {
            return -1;
        }

        $index = 0;

        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param array<int, array<int, mixed>> $rawRows
     * @return array<int, array<string, mixed>>
     */
    protected function rowsToAssociative(array $rawRows): array
    {
        while ($rawRows !== [] && $this->rowIsEmpty($rawRows[0])) {
            array_shift($rawRows);
        }

        if ($rawRows === []) {
            return [];
        }

        $headerRow = array_shift($rawRows);
        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow);

        if (! in_array('sku', $headers, true) || ! in_array('name', $headers, true)) {
            throw ValidationException::withMessages([
                'importFile' => 'The file must include at least the SKU and Name columns. Use the downloadable template for the expected format.',
            ]);
        }

        $rows = [];
        $spreadsheetRow = 1;

        foreach ($rawRows as $rawRow) {
            $spreadsheetRow++;

            if ($this->rowIsEmpty($rawRow)) {
                continue;
            }

            $row = ['_row' => $spreadsheetRow];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = isset($rawRow[$index])
                    ? trim((string) $rawRow[$index])
                    : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, mixed> $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }
}
