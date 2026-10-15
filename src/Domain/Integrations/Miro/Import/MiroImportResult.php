<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

final class MiroImportResult
{
    public int $createdObjects = 0;

    public int $linkedItems = 0;

    public int $createdRelations = 0;

    public int $skippedRows = 0;

    public function isEmpty(): bool
    {
        return $this->createdObjects + $this->linkedItems + $this->createdRelations === 0;
    }
}
