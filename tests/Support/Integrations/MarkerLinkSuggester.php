<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Service\Suggestion\LinkSuggesterInterface;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionCandidate;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionContext;
use App\Domain\StoryObject\Entity\Enum\TargetType;

final class MarkerLinkSuggester implements LinkSuggesterInterface
{
    public const SOURCE = 'marker';

    public const MARKER = '[marker]';

    public const SCORE = 0.42;

    public function suggest(ExternalItem $item, LinkSuggestionContext $context): iterable
    {
        if (!str_contains((string) $item->getTitle(), self::MARKER)) {
            return [];
        }

        yield LinkSuggestionCandidate::create(self::SOURCE, self::SCORE, TargetType::Item);
    }
}
