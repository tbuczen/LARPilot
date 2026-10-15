<?php

namespace App\Domain\Integrations\DTO;

final readonly class SourceSyncPage
{
    /**
     * @param list<SyncedExternalItem> $items
     * @param array<string, mixed>|null $nextCursor
     * @param list<string> $missingExternalIds
     */
    public function __construct(
        public array $items,
        public ?array $nextCursor,
        public bool $hasMorePages,
        public array $missingExternalIds = [],
    ) {
    }
}
