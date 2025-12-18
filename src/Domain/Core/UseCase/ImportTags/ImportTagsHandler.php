<?php

namespace App\Domain\Core\UseCase\ImportTags;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\Integrations\Repository\SharedFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class ImportTagsHandler
{
    private array $skippedTags = [];

    public function __construct(
        private readonly LarpRepository $larpRepository,
        private readonly TagRepository $tagRepository,
        private readonly SharedFileRepository $sharedFileRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws Exception|ORMException
     */
    public function handle(ImportTagsCommand $command): array
    {
        ini_set('max_execution_time', 300);
        $chunkSize = 20;
        $startingRow = $command->meta['startingRow'] ?? 1;
        $filteredRows = array_slice($command->rows, $startingRow - 1, null, true);
        $chunks = array_chunk($filteredRows, $chunkSize, true);

        $larp = $this->larpRepository->find($command->larpId);
        $file = $this->sharedFileRepository->find($command->externalFileId);
        $existingTagsMap = $this->getExistingTagsMap($command->larpId);

        Assert::notNull($larp, sprintf('LARP %s not found for the tag import handler.', $command->larpId));

        $this->entityManager->beginTransaction();
        try {
            foreach ($chunks as $chunk) {
                foreach ($chunk as $rowNo => $row) {
                    $tagTitle = $this->getFieldValue($row, $command->mapping, 'title');
                    if (!$tagTitle) {
                        continue; // Skip rows missing a tag title.
                    }

                    $existingTag = $existingTagsMap[$tagTitle] ?? null;

                    // If tag exists and has a description, skip it
                    if ($existingTag && $existingTag->getDescription() !== null) {
                        $this->skippedTags[] = $tagTitle;
                        continue;
                    }

                    // Create new tag or update existing one with null description
                    if ($existingTag) {
                        $tag = $existingTag;
                    } else {
                        $tag = new Tag();
                        $tag->setLarp($this->entityManager->getReference(Larp::class, Uuid::fromString($command->larpId)));
                    }

                    // Process each mapping: iterate over the mapping configuration.
                    foreach ($command->mapping as $fieldName => $col) {
                        $value = $row[$col] ?? null;
                        if ($value === null) {
                            continue;
                        }

                        // For scalar properties, we try a dynamic setter.
                        $setter = 'set' . ucfirst($fieldName);

                        if (method_exists($tag, $setter)) {
                            $tag->$setter($value);
                        } else {
                            throw new \LogicException("Setter $setter does not exist for Tag");
                        }
                    }

                    // Note: External references for tags are not yet supported (Tag is not a StoryObject)
                    // TODO: Add external reference support when needed

                    $this->entityManager->persist($tag);
                }
                $this->entityManager->flush();
                $file = $this->sharedFileRepository->find($command->externalFileId);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $ex) {
            $this->entityManager->rollback();
            throw $ex;
        }

        return ['skipped' => $this->skippedTags];
    }

    private function getFieldValue(array $row, array $mapping, string $field): ?string
    {
        foreach ($mapping as $mappedField => $col) {
            if ($mappedField === $field) {
                return $row[$col] ?? null;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    private function getExistingTagsMap(?string $larpId): array
    {
        $existingTags = $this->tagRepository->findBy(['larp' => Uuid::fromString($larpId)]);
        $existingMap = [];
        foreach ($existingTags as $tag) {
            $existingMap[$tag->getTitle()] = $tag;
        }
        return $existingMap;
    }
}
