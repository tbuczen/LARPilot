<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Message;

use Symfony\Component\Uid\Uuid;

/**
 * Message to trigger async reindexing of all story objects in a LARP.
 */
final readonly class ReindexLarpMessage
{
    public function __construct(
        public Uuid $larpId,
    ) {
    }
}
