<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Browser;

use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;

final readonly class MiroBoardFrameGroup
{
    /**
     * @param list<MiroBoardEntry> $items
     */
    public function __construct(
        public MiroBoardEntry $frame,
        public array $items,
    ) {
    }
}
