<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\DTO\ExternalReferenceLink;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Service\Url\ExternalUrlParser;
use App\Domain\StoryObject\Entity\StoryObject;

final readonly class StorySourceLinkService
{
    public function __construct(
        private ExternalReferenceLinker $linker,
        private ExternalItemRepository $itemRepository,
        private ExternalUrlParser $urlParser,
    ) {
    }

    public function linkCachedItem(StoryObject $storyObject, string $itemId, ReferenceRole $role, User $linkedBy): ?ExternalReference
    {
        $larp = $storyObject->getLarp();
        if ($larp === null) {
            return null;
        }

        $item = $this->itemRepository->findOneForLarp($larp, $itemId);
        if (!$item instanceof ExternalItem) {
            return null;
        }

        return $this->linker->linkItem($storyObject, $item, $role, $linkedBy);
    }

    public function linkUrl(StoryObject $storyObject, string $url, ReferenceRole $role, User $linkedBy): ?ExternalReference
    {
        $larp = $storyObject->getLarp();
        $parsedUrl = $this->urlParser->parse($url);
        if ($larp === null || $parsedUrl === null) {
            return null;
        }

        $cachedItem = $this->itemRepository->findOneForLarpByExternalId($larp, $parsedUrl->provider, $parsedUrl->externalId);
        if ($cachedItem instanceof ExternalItem) {
            return $this->linker->linkItem($storyObject, $cachedItem, $role, $linkedBy);
        }

        return $this->linker->link($storyObject, new ExternalReferenceLink(
            provider: $parsedUrl->provider,
            externalId: $parsedUrl->externalId,
            referenceType: $parsedUrl->referenceType,
            role: $role,
            url: $parsedUrl->url,
            linkedBy: $linkedBy,
        ));
    }
}
