<?php

namespace App\Domain\Core\UseCase\ImportTags;

readonly class ImportTagsCommand
{
    /**
     * @param string $larpId The LARP identifier.
     * @param array $rows An array of data rows to import.
     *                Each row should be an associative array (e.g. ['title' => 'Ambition', 'description' => 'A tag for ambitious characters']).
     * @param array $mapping The mapping configuration used to build the rows.
     * @param array $meta Thing like starting column, object name, url, position - to speed up and make localisation of data easier.
     * @param string $externalFileId The external file ID (for example, the Google Spreadsheet ID) if applicable.
     * @param array $additionalFileData Additional file metadata (e.g., sheetId for spreadsheets).
     */
    public function __construct(
        public string $larpId,
        public array $rows,
        public array $mapping,
        public array $meta,
        public string $externalFileId,
        public array $additionalFileData = [],
    ) {
    }
}
