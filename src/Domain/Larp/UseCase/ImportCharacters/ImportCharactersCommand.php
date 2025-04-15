<?php

namespace App\Domain\Larp\UseCase\ImportCharacters;


readonly class ImportCharactersCommand
{

    /**
     * @param string $larpId The LARP identifier.
     * @param array $rows An array of data rows to import.
     *                Each row should be an associative array (e.g. ['faction' => 'Warrior', 'characterName' => 'Alice', 'inGameName' => 'Alicia']).
     * @param array $mapping The mapping configuration used to build the rows.
     * @param string $externalFileId The external file ID (for example, the Google Spreadsheet ID) if applicable.
     * @param bool $force If true - it will re-import the character with the file data even if it already exists in the system
     */
    public function __construct(
        public string $larpId,
        public array $rows,
        public array $mapping,
        public string $externalFileId,
        public bool $force = false,
    ) {
    }
}
