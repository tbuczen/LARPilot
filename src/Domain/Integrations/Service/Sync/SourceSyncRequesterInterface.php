<?php

namespace App\Domain\Integrations\Service\Sync;

use App\Domain\Integrations\Entity\ExternalSource;

interface SourceSyncRequesterInterface
{
    public function requestSync(ExternalSource $source): void;
}
