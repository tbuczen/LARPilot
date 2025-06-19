<?php

namespace App\Service\StoryObject;

use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObjectLogEntry;
use Doctrine\ORM\EntityManagerInterface;

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
        /** @var \Gedmo\Loggable\Entity\Repository\LogEntryRepository $repo */
        $repo = $this->em->getRepository(StoryObjectLogEntry::class);
        $entries = $repo->getLogEntries($object);

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
