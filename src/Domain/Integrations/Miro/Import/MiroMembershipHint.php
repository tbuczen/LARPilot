<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;

final readonly class MiroMembershipHint
{
    public function __construct(
        public MiroBoardEntry $characterEntry,
        public MiroImportTarget $character,
        public MiroImportTarget $faction,
        public bool $alreadyMember,
    ) {
    }
}
