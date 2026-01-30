<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Message;

use Symfony\Component\Uid\Uuid;

/**
 * Message to trigger async indexing of a lore document.
 */
final readonly class IndexLoreDocumentMessage
{
    public function __construct(
        public Uuid $documentId,
    ) {
    }
}
