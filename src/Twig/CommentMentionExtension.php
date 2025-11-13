<?php

declare(strict_types=1);

namespace App\Twig;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Service\CommentMentionParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for parsing comment mentions
 */
class CommentMentionExtension extends AbstractExtension
{
    public function __construct(
        private readonly CommentMentionParser $mentionParser,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('parse_mentions', [$this, 'parseMentions'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Parse Quill mentions in content and convert to hyperlinks
     */
    public function parseMentions(string $content, Larp $larp): string
    {
        return $this->mentionParser->parseMentions($content, $larp);
    }
}
