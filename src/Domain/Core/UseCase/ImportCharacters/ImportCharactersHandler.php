<?php

namespace App\Domain\Core\UseCase\ImportCharacters;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\SharedFileRepository;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\Integrations\Service\IntegrationServiceInterface;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\FactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class ImportCharactersHandler
{
    private array $cache;
    private ?IntegrationServiceInterface $integrationService = null;

    public function __construct(
        private readonly LarpRepository          $larpRepository,
        private readonly CharacterRepository $characterRepository,
        private readonly FactionRepository   $factionRepository,
        private readonly SharedFileRepository    $sharedFileRepository,
        private readonly IntegrationManager      $integrationManager,
        private readonly EntityManagerInterface  $entityManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(ImportCharactersCommand $command): void
    {
        ini_set('max_execution_time', 300);
        $chunkSize = 20;
        $startingRow = $command->meta['startingRow'] ?? 1;
        $filteredRows = array_slice($command->rows, $startingRow - 1, null, true);
        $chunks = array_chunk($filteredRows, $chunkSize, true);

        $larp = $this->larpRepository->find($command->larpId);
        $file = $this->sharedFileRepository->find($command->externalFileId);
        $existingCharactersMap = $this->getExistingCharactersMap($command->larpId);

        $this->integrationService = $this->integrationManager->getService($file->getIntegration());

        Assert::notNull($larp, sprintf('LARP %s not found for the character import handler.', $command->larpId));

        $this->entityManager->beginTransaction();
        try {
            foreach ($chunks as $chunk) {
                foreach ($chunk as $rowNo => $row) {
                    $characterName = $this->getFieldValue($row, $command->mapping, 'title');
                    if (!$characterName) {
                        continue; // Skip rows missing a character name.
                    }

                    $character = $existingCharactersMap[$characterName] ?? null;
                    if ($character && !$command->force) {
                        continue;
                    }

                    $character = new Character();
                    $character->setLarp($this->entityManager->getReference(Larp::class, Uuid::fromString($command->larpId)));

                    // Process each mapping: iterate over the mapping configuration.
                    foreach ($command->mapping as $fieldName => $col) {
                        $value = $row[$col] ?? null;
                        if ($value === null && !$command->force) {
                            continue;
                        }

                        if ($fieldName === 'factions') {
                            // For faction, we need to find or create and cache it.
                            $this->handleFaction($value, $command->larpId, $character);
                        } else {
                            // For scalar properties, we try a dynamic setter.
                            $setter = 'set' . ucfirst($fieldName);

                            if (method_exists($character, $setter)) {
                                $character->$setter($value);
                            } else {
                                throw new \LogicException("Setter $setter does not exist for Character");
                            }
                        }
                    }
                    $this->createReference($character, $rowNo, $file, $command->additionalFileData);
                    $this->entityManager->persist($character);
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

    private function handleFaction(mixed $value, string $larpId, Character $character): void
    {
        $factionName = trim((string) $value);
        if (!isset($this->cache[$factionName])) {
            $faction = $this->factionRepository->findByOrCreate($factionName, $larpId);
            $this->cache[$factionName] = $faction;
        }
        $character->addFaction($this->cache[$factionName]);
    }

    private function createReference(Character $character, int|string $rowNo, SharedFile $file, array $additionalData = []): void
    {
        $reference = new ExternalReference();
        $reference->setStoryObject($character);
        $reference->setProvider($file->getIntegration()->getProvider());
        $reference->setExternalId($rowNo + 1);
        $reference->setReferenceType(ReferenceType::SpreadsheetRow);
        $reference->setName($character->getTitle());
        $reference->setUrl($this->integrationService->createReferenceUrl($file, ReferenceType::SpreadsheetRow, $reference->getExternalId(), $additionalData));
        $reference->setRole(ReferenceRole::Primary);

        $this->entityManager->persist($reference);
    }

    private function getExistingCharactersMap(?string $larpId): array
    {
        $existingCharacters = $this->characterRepository->findBy(['larp' => Uuid::fromString($larpId)]);
        $existingMap = [];
        foreach ($existingCharacters as $char) {
            $existingMap[$char->getTitle()] = $char;
        }
        return $existingMap;
    }
}
