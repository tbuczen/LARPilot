<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\DTO\ExternalReferenceLink;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Event\ExternalReferenceLinked;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class ExternalReferenceLinker
{
    public function __construct(
        private ExternalReferenceRepository $referenceRepository,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function link(StoryObject $storyObject, ExternalReferenceLink $link): ExternalReference
    {
        $existingReference = $this->referenceRepository->findOneForStoryObject($storyObject, $link->provider, $link->externalId);
        if ($existingReference instanceof ExternalReference) {
            return $existingReference;
        }

        $reference = (new ExternalReference())
            ->setStoryObject($storyObject)
            ->setExcerpt($link->excerpt)
            ->setExternalUpdatedAt($link->externalUpdatedAt);
        $reference->setProvider($link->provider);
        $reference->setExternalId($link->externalId);
        $reference->setReferenceType($link->referenceType);
        $reference->setRole($link->role);
        $reference->setName($link->name);
        $reference->setUrl($link->url);
        $creator = $link->linkedBy ?? $storyObject->getCreatedBy();
        if ($creator !== null) {
            $reference->setCreatedBy($creator);
        }

        return $this->persistNewReference($reference);
    }

    public function linkItem(
        StoryObject $storyObject,
        ExternalItem $item,
        ReferenceRole $role = ReferenceRole::Mention,
        ?User $linkedBy = null,
    ): ExternalReference {
        $source = $item->getSource();
        $provider = $source->getIntegration()->getProvider();

        $existingReference = $this->referenceRepository->findOneForStoryObject($storyObject, $provider, $item->getExternalId());
        if ($existingReference instanceof ExternalReference) {
            return $existingReference;
        }

        $reference = (new ExternalReference())
            ->setStoryObject($storyObject)
            ->setSource($source)
            ->setExternalItem($item)
            ->setExcerpt($item->getExcerpt())
            ->setExternalUpdatedAt($item->getExternalUpdatedAt())
            ->setLastSeenAt($item->getLastSeenAt());
        $reference->setProvider($provider);
        $reference->setExternalId($item->getExternalId());
        $reference->setReferenceType($item->getKind()->referenceType());
        $reference->setRole($role);
        $reference->setName($item->getTitle());
        $reference->setUrl($item->getUrl());
        $creator = $linkedBy ?? $source->getIntegration()->getCreatedBy();
        if ($creator !== null) {
            $reference->setCreatedBy($creator);
        }

        $item->setReviewStatus(ExternalItemReviewStatus::Linked);

        return $this->persistNewReference($reference);
    }

    public function unlink(StoryObject $storyObject, LarpIntegrationProvider $provider, string $externalId): bool
    {
        $reference = $this->referenceRepository->findOneForStoryObject($storyObject, $provider, $externalId);
        if (!$reference instanceof ExternalReference) {
            return false;
        }

        $storyObject->getExternalReferences()->removeElement($reference);
        $this->entityManager->remove($reference);
        $this->entityManager->flush();

        $item = $reference->getExternalItem();
        if ($item instanceof ExternalItem && $item->getReviewStatus() === ExternalItemReviewStatus::Linked && $this->referenceRepository->countForExternalItem($item) === 0) {
            $item->setReviewStatus(ExternalItemReviewStatus::Unreviewed);
            $this->entityManager->flush();
        }

        return true;
    }

    public function unlinkItem(StoryObject $storyObject, ExternalItem $item): bool
    {
        return $this->unlink($storyObject, $item->getSource()->getIntegration()->getProvider(), $item->getExternalId());
    }

    private function persistNewReference(ExternalReference $reference): ExternalReference
    {
        $reference->getStoryObject()?->getExternalReferences()->add($reference);
        $this->entityManager->persist($reference);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new ExternalReferenceLinked($reference));

        return $reference;
    }
}
