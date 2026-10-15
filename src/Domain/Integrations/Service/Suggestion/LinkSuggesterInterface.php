<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Integrations\Entity\ExternalItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface LinkSuggesterInterface
{
    public const TAG = 'app.integrations.link_suggester';

    /**
     * @return iterable<LinkSuggestionCandidate>
     */
    public function suggest(ExternalItem $item, LinkSuggestionContext $context): iterable;
}
