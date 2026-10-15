<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface MiroImportRuleInterface
{
    public const TAG = 'app.miro_import_rule';

    public function suggest(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion;
}
