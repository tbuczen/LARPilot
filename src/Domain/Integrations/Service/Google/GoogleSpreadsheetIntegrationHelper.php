<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Form\Models\ExternalResourceMappingModel;
use App\Domain\Integrations\Form\Models\SpreadsheetMappingModel;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Common\Collections\Collection;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

readonly class GoogleSpreadsheetIntegrationHelper
{
    public function __construct(
        private GoogleClientManager $googleClientManager,
    ) {
    }

    public function fetchSpreadsheetSheetIdByName(SharedFile $sharedFile, SpreadsheetMappingModel $mapping): string
    {
        $client = $this->googleClientManager->createServiceAccountClient();
        $sheetsService = new Sheets($client);

        $spreadsheet = $sheetsService->spreadsheets->get($sharedFile->getFileId());

        foreach ($spreadsheet->getSheets() as $sheet) {
            $properties = $sheet->getProperties();
            if ($properties->getTitle() === $mapping->sheetName) {
                return (string) $properties->getSheetId(); // This is the GID
            }
        }

        throw new \RuntimeException(sprintf(
            'Sheet "%s" not found in spreadsheet "%s".',
            $mapping->sheetName,
            $sharedFile->getFileId()
        ));
    }

    public function fetchSpreadsheetRows(SharedFile $sharedFile, SpreadsheetMappingModel $mapping): array
    {
        $client = $this->googleClientManager->createServiceAccountClient();
        $sheetsService = new Sheets($client);

        try {
            $range = $mapping->sheetName . '!A:' . $mapping->endColumn;
            $response = $sheetsService->spreadsheets_values->get($sharedFile->getFileId(), $range);
            $rows = $response->getValues();
            return $this->remapWithColumnLetters($rows, $mapping->endColumn);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to read spreadsheet: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function remapWithColumnLetters(array $rows, string $endColumn): array
    {
        $columnLetters = $this->generateColumnRange($endColumn);
        $maxColumns = count($columnLetters);

        $remapped = [];
        foreach ($rows as $row) {
            $paddedRow = array_pad($row, $maxColumns, null);
            $remapped[] = array_combine($columnLetters, $paddedRow);
        }

        return $remapped;
    }

    private function generateColumnRange(string $end, string $start = 'A'): array
    {
        $range = [];
        $current = $start;
        while (true) {
            $range[] = $current;
            if ($current === $end) {
                break;
            }
            $current = $this->incrementColumn($current);
        }
        return $range;
    }

    private function incrementColumn(string $column): string
    {
        $length = strlen($column);
        $column = strtoupper($column);
        $i = $length - 1;

        while ($i >= 0) {
            if ($column[$i] !== 'Z') {
                $column[$i] = chr(ord($column[$i]) + 1);
                return substr($column, 0, $i + 1) . str_repeat('A', $length - $i - 1);
            }
            $i--;
        }

        return 'A' . str_repeat('A', $length);
    }

    public function buildSpreadsheetRow(ExternalResourceMappingModel $spreadsheetMapping, StoryObject $storyObject): array
    {
        $row = [];

        foreach ($spreadsheetMapping->mappings as $storyObjectProperty => $columnLetter) {
            $value = $this->extractPropertyValue($storyObject, $storyObjectProperty);
            $row[$columnLetter] = $value ?? '';
        }

        return $row;
    }

    private function extractPropertyValue(StoryObject $storyObject, string $propertyName): mixed
    {
        $getter = 'get' . ucfirst($propertyName);
        if (method_exists($storyObject, $getter)) {
            $value = $storyObject->$getter();

            if ($value instanceof Collection || is_array($value)) {
                // Try to map to string if possible
                return $this->convertCollectionToString($value);
            }

            return $value;
        }

        if (property_exists($storyObject, $propertyName)) {
            $value = $storyObject->$propertyName;

            if ($value instanceof Collection || is_array($value)) {
                return $this->convertCollectionToString($value);
            }

            return $value;
        }

        throw new \RuntimeException(sprintf('Cannot extract property "%s" from %s', $propertyName, $storyObject::class));
    }

    private function convertCollectionToString(iterable $collection): string
    {
        $values = [];

        foreach ($collection as $item) {
            if (method_exists($item, 'getName')) {
                $values[] = $item->getName();
            } elseif (method_exists($item, '__toString')) {
                $values[] = (string) $item;
            } else {
                $values[] = (string) $item;
            }
        }

        return implode(', ', $values);
    }

    public function appendRowToSpreadsheet(SharedFile $sharedFile, SpreadsheetMappingModel $mapping, array $newRow): void
    {
        $spreadsheetId = $sharedFile->getFileId();
        $sheetName = $mapping->sheetName;
        $endColumn = $mapping->endColumn;

        $client = $this->googleClientManager->createServiceAccountClient();
        $sheetsService = new Sheets($client);

        $fullRow = $this->buildFullSpreadsheetRow($newRow, $endColumn);

        $spreadsheetMetadata = $this->fetchSpreadsheetMetadata($sharedFile, $sheetName);
        $nextRow = $this->findNextAvailableRowFromMetadata(
            $spreadsheetMetadata,
            $sheetName,
            'A',
            $endColumn
        );

        $range = sprintf('%s!A%d:%s%d', $sheetName, $nextRow, $endColumn, $nextRow);

        $valueRange = new ValueRange([
            'values' => [
                $fullRow,
            ],
        ]);
        try {
            $sheetsService->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $valueRange,
                [
                    'valueInputOption' => 'USER_ENTERED',
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update spreadsheet: ' . $e->getMessage(), 0, $e);
        }
    }


    private function buildFullSpreadsheetRow(array $newRow, string $endColumn): array
    {
        $fullRow = [];
        $columnRange = $this->generateColumnRange($endColumn);

        foreach ($columnRange as $columnLetter) {
            $fullRow[] = $newRow[$columnLetter] ?? '';
        }

        return $fullRow;
    }

    private function fetchSpreadsheetMetadata(SharedFile $sharedFile, string $sheetName): Sheets\Spreadsheet
    {
        $client = $this->googleClientManager->createServiceAccountClient();
        $sheetsService = new Sheets($client);

        return $sheetsService->spreadsheets->get(
            $sharedFile->getFileId(),
            [
                'ranges' => [$sheetName],
                'fields' => 'sheets.properties.title,sheets.data.rowData.values.formattedValue'
            ]
        );
    }

    private function findNextAvailableRowFromMetadata(Sheets\Spreadsheet $spreadsheet, string $sheetName, string $startColumn, string $endColumn): int
    {
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() !== $sheetName) {
                continue;
            }
            $rows = $sheet->getData()[0]->getRowData();

            $columnRange = $this->generateColumnRange($endColumn, $startColumn);
            $columnCount = count($columnRange);

            foreach ($rows as $index => $row) {
                $cells = $row->getValues();
                $cellsInRange = array_slice($cells, 0, $columnCount); // Only slice needed columns

                $hasContent = false;
                foreach ($cellsInRange as $cell) {
                    if (!empty($cell->getFormattedValue())) {
                        $hasContent = true;
                        break;
                    }
                }

                if (!$hasContent) {
                    return $index + 1; // Row numbers are 1-based
                }
            }

            return count($rows) + 1;
        }

        throw new \RuntimeException('Sheet not found: ' . $sheetName);
    }
}
