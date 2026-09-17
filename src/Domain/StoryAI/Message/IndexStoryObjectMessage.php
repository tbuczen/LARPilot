<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Message;

use Symfony\Component\Uid\Uuid;

/**
 * Message to trigger async indexing of a story object.
 */
final readonly class IndexStoryObjectMessage
{
    public function __construct(
        public Uuid $storyObjectId,
    ) {
    }
}
