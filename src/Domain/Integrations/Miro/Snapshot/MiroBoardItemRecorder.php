<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class MiroBoardItemRecorder
{
    public function __construct(
        private ExternalItemRepository $externalItemRepository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function record(ExternalSource $board, MiroBoardEntry $entry): ExternalItem
    {
        $storedItem = $this->externalItemRepository->findOneByExternalId($board, $entry->externalId);
        if ($storedItem instanceof ExternalItem) {
            return $storedItem;
        }

        $syncedItem = $entry->toSyncedExternalItem();
        $externalItem = (new ExternalItem())
            ->setExternalId($syncedItem->externalId)
            ->setKind($syncedItem->kind)
            ->setTitle($syncedItem->title)
            ->setExcerpt($syncedItem->excerpt)
            ->setUrl($syncedItem->url)
            ->setParentExternalId($syncedItem->parentExternalId)
            ->setExternalUpdatedAt($syncedItem->externalUpdatedAt)
            ->setRaw($syncedItem->raw)
            ->setLastSeenAt($this->clock->now())
            ->setAvailability($syncedItem->availability);
        $board->addItem($externalItem);
        $this->entityManager->persist($externalItem);
        $this->entityManager->flush();

        return $externalItem;
    }
}
