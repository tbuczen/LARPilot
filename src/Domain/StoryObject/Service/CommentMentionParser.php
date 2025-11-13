<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service to parse Quill mentions in comment content and convert them to clickable hyperlinks
 */
class CommentMentionParser
{
    public function __construct(
        private readonly StoryObjectRepository $storyObjectRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Parse Quill mention spans and convert them to hyperlinks
     *
     * @param string $content HTML content with Quill mentions
     * @param Larp $larp LARP context for generating URLs
     * @return string Parsed content with mentions as hyperlinks
     */
    public function parseMentions(string $content, Larp $larp): string
    {
        // Match Quill mention spans - the full outer span
        // Pattern matches both HTML entity (&#xFEFF;) and actual Unicode character (\x{FEFF})
        // Structure: <span class="mention"...>&#xFEFF;<nested spans>&#xFEFF;</span>
        $pattern = '/<span class="mention"[^>]*data-id="([^"]+)"[^>]*data-value="([^"]+)"[^>]*>(?:&#xFEFF;|\x{FEFF})<span contenteditable="false">.*?<\/span>(?:&#xFEFF;|\x{FEFF})<\/span>/su';

        $result = preg_replace_callback($pattern, function ($matches) use ($larp) {
            $storyObjectId = $matches[1];
            $mentionValue = $matches[2];

            // Look up the story object
            $storyObject = $this->storyObjectRepository->find($storyObjectId);

            if (!$storyObject) {
                // If story object not found, return plain text mention
                return '@' . htmlspecialchars($mentionValue, ENT_QUOTES, 'UTF-8');
            }

            // Generate URL based on story object type
            $targetType = strtolower($storyObject->getTargetType()->value);
            $routeName = 'backoffice_larp_story_' . $targetType . '_modify';

            try {
                $url = $this->urlGenerator->generate($routeName, [
                    'larp' => $larp->getId(),
                    $targetType => $storyObject->getId(),
                ]);

                // Return as clickable link with mention styling
                return sprintf(
                    '<a href="%s" class="mention-link" title="%s">@%s</a>',
                    htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($mentionValue, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($mentionValue, ENT_QUOTES, 'UTF-8')
                );
            } catch (\Exception) {
                // If route generation fails, return plain text mention
                return '@' . htmlspecialchars($mentionValue, ENT_QUOTES, 'UTF-8');
            }
        }, $content);

        return $result ?? $content;
    }
}
