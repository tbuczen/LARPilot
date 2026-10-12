<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\DTO\SourceContent;
use App\Domain\Integrations\Entity\ExternalReference;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface SourceContentProviderInterface
{
    public const TAG = 'app.integrations.source_content_provider';

    public function supports(ExternalReference $reference): bool;

    public function fetchContent(ExternalReference $reference): SourceContent;
}
