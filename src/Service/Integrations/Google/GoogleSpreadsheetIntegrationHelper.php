<?php

namespace App\Service\Integrations\Google;

use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Entity\StoryObject;
use App\Form\Models\ExternalResourceMappingModel;
use App\Form\Models\SpreadsheetMappingModel;
use Doctrine\Common\Collections\Collection;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Webmozart\Assert\Assert;

readonly class GoogleSpreadsheetIntegrationHelper
{

    public function __construct(
        private GoogleClientManager $googleClientManager,
    ) {
    }

    public function fetchSpreadsheetRows(SharedFile $sharedFile, ExternalResourceMappingModel $mapping): array
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
        $columnLetters = $this->generateColumnRange('A', $endColumn);
        $maxColumns = count($columnLetters);

        $remapped = [];
        foreach ($rows as $row) {
            $paddedRow = array_pad($row, $maxColumns, null);
            $remapped[] = array_combine($columnLetters, $paddedRow);
        }

        return $remapped;
    }

    private function generateColumnRange(string $start, string $end): array
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

        throw new \RuntimeException(sprintf('Cannot extract property "%s" from %s', $propertyName, get_class($storyObject)));
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

        $range = $sheetName;
        $body = [
            'values' => [
                $this->convertRowToOrderedArray($newRow),
            ],
        ];

        $client = $this->googleClientManager->createServiceAccountClient();
        $sheetsService = new Sheets($client);

        $sheetsService->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            new ValueRange($body),
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    private function convertRowToOrderedArray(array $rowData): array
    {
        // Example: sort C, F, G, N correctly
        ksort($rowData); // Column letters sort alphabetically

        $ordered = [];

        foreach ($rowData as $column => $value) {
            $ordered[] = $value;
        }

        return $ordered;
    }

}