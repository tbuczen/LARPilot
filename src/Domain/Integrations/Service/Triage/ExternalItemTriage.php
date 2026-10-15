<?php

namespace App\Domain\Integrations\Service\Triage;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ExternalItemTriage
{
    public const MAX_TITLE_LENGTH = 255;

    public function __construct(
        private ExternalReferenceLinker $linker,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param iterable<ExternalItem> $items
     */
    public function link(iterable $items, StoryObject $storyObject, User $linkedBy): int
    {
        $linkedCount = 0;
        foreach ($items as $item) {
            $this->linker->linkItem($storyObject, $item, ReferenceRole::Mention, $linkedBy);
            $linkedCount++;
        }

        return $linkedCount;
    }

    /**
     * @param iterable<ExternalItem> $items
     */
    public function ignore(iterable $items): int
    {
        return $this->changeStatus($items, ExternalItemReviewStatus::Unreviewed, ExternalItemReviewStatus::Ignored);
    }

    /**
     * @param iterable<ExternalItem> $items
     */
    public function restore(iterable $items): int
    {
        return $this->changeStatus($items, ExternalItemReviewStatus::Ignored, ExternalItemReviewStatus::Unreviewed);
    }

    public function createFromItem(ExternalItem $item, TargetType $targetType, User $createdBy): StoryObject
    {
        $storyObjectClass = $targetType->getEntityClass();
        if (!is_subclass_of($storyObjectClass, StoryObject::class)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a story object type.', $targetType->value));
        }

        $larp = $item->getSource()->getIntegration()->getLarp();

        return $this->entityManager->wrapInTransaction(function () use ($item, $storyObjectClass, $larp, $createdBy): StoryObject {
            $storyObject = new $storyObjectClass();
            $storyObject->setTitle(mb_substr($item->getTitle() ?? $item->getExternalId(), 0, self::MAX_TITLE_LENGTH));
            $storyObject->setDescription($this->description($item->getExcerpt()));
            $storyObject->setLarp($larp);
            $storyObject->setCreatedBy($createdBy);
            $this->applyMappedTags($storyObject, $item);

            $this->entityManager->persist($storyObject);
            $this->entityManager->flush();

            $this->linker->linkItem($storyObject, $item, ReferenceRole::Primary, $createdBy);

            return $storyObject;
        });
    }

    /**
     * @param iterable<ExternalItem> $items
     */
    private function changeStatus(iterable $items, ExternalItemReviewStatus $from, ExternalItemReviewStatus $to): int
    {
        $changedCount = 0;
        foreach ($items as $item) {
            if ($item->getReviewStatus() !== $from) {
                continue;
            }

            $item->setReviewStatus($to);
            $changedCount++;
        }

        $this->entityManager->flush();

        return $changedCount;
    }

    private function description(?string $excerpt): ?string
    {
        if ($excerpt === null || trim($excerpt) === '') {
            return null;
        }

        $paragraphs = array_map(
            static fn (string $line): string => '<p>' . htmlspecialchars($line, ENT_QUOTES | ENT_HTML5) . '</p>',
            array_filter(array_map('trim', explode("\n", $excerpt)), static fn (string $line): bool => $line !== ''),
        );

        return implode('', $paragraphs);
    }

    private function applyMappedTags(StoryObject $storyObject, ExternalItem $item): void
    {
        if (!method_exists($storyObject, 'addTag')) {
            return;
        }

        foreach ($item->getSuggestions() as $suggestion) {
            $tag = $suggestion->getTag();
            if (!$tag instanceof Tag) {
                continue;
            }

            $storyObject->addTag($tag);
        }
    }
}
