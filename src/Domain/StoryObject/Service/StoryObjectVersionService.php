<?php

namespace App\Domain\StoryObject\Service;

use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Entity\StoryObjectLogEntry;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;

final readonly class StoryObjectVersionService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return array<int, array{entry: StoryObjectLogEntry, diff: array<string, mixed>}>
     */
    public function getVersionHistory(StoryObject $object): array
    {
        /** @var LogEntryRepository<StoryObjectLogEntry> $repo */
        $repo = $this->em->getRepository(StoryObjectLogEntry::class);
        /** @var array<StoryObjectLogEntry> $entries */
        $entries = $repo->getLogEntries($object); // @phpstan-ignore argument.type (Gedmo generics issue)

        $history = [];
        $previousData = null;
        foreach (array_reverse($entries) as $entry) {
            $data = $entry->getData() ?? [];
            $diff = [];
            if ($previousData !== null) {
                foreach ($data as $field => $value) {
                    $old = $previousData[$field] ?? null;
                    if ($old !== $value) {
                        $diff[$field] = ['old' => $old, 'new' => $value];
                    }
                }
            }
            $history[] = ['entry' => $entry, 'diff' => $diff];
            $previousData = $data;
        }

        return array_reverse($history);
    }
}
