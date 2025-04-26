<?php

namespace App\Domain\Larp\UseCase\ImportCharacters;

use App\Entity\Enum\ReferenceRole;
use App\Entity\Enum\ReferenceType;
use App\Entity\ExternalReference;
use App\Entity\Larp;
use App\Entity\SharedFile;
use App\Entity\StoryObject\LarpCharacter;
use App\Repository\LarpRepository;
use App\Repository\SharedFileRepository;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\StoryObject\LarpFactionRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Integrations\IntegrationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Webmozart\Assert\Assert;

class ImportCharactersHandler
{

    private array $cache;
    private ?IntegrationServiceInterface $integrationService;

    public function __construct(
        private readonly LarpRepository          $larpRepository,
        private readonly LarpCharacterRepository $characterRepository,
        private readonly LarpFactionRepository   $factionRepository,
        private readonly SharedFileRepository    $sharedFileRepository,
        private readonly IntegrationManager      $integrationManager,
        private readonly EntityManagerInterface  $entityManager
    )
    {
    }

    /**
     * @throws Exception
     */
    public function handle(ImportCharactersCommand $command): void
    {
        $larp = $this->larpRepository->find($command->larpId);
        $file = $this->sharedFileRepository->find($command->externalFileId);

        $this->integrationService = $this->integrationManager->getService($file->getIntegration());

        Assert::notNull($larp, sprintf('LARP %s not found for the character import handler.', $command->larpId));

        $this->entityManager->beginTransaction();
        try {
            // Cache to store factions already found/created for this import
            foreach ($command->rows as $rowNo => $row) {
                if ($rowNo < $command->meta['startingRow'] - 1) {
                    continue;
                }
                $characterName = $this->getFieldValue($row, $command->mapping, 'name');
                if (!$characterName) {
                    continue; // Skip rows missing a character name.
                }

                $character = $this->characterRepository->findOneBy([
                    'name' => $characterName,
                    'larp' => $larp,
                ]);
                if ($character && !$command->force) {
                    continue;
                }

                $character = new LarpCharacter();
                $character->setLarp($larp);

                // Process each mapping: iterate over the mapping configuration.
                foreach ($command->mapping as $fieldName => $col) {
                    $value = $row[$col] ?? null;
                    if ($value === null && !$command->force) {
                        continue;
                    }

                    if ($fieldName === 'factions') {
                        // For faction, we need to find or create and cache it.
                        $this->handleFaction($value, $larp, $character);
                    } else {
                        // For scalar properties, we try a dynamic setter.
                        $setter = 'set' . ucfirst($fieldName);

                        if (method_exists($character, $setter)) {
                            $character->$setter($value);
                        } else {
                            throw new \LogicException("Setter $setter does not exist for LarpCharacter");
                        }
                    }
                }
                $this->createReference($character, $rowNo, $file);
                $this->entityManager->persist($character);
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

    private function handleFaction(mixed $value, Larp $larp, LarpCharacter $character): void
    {
        $factionName = trim($value);
        if (!isset($this->cache[$factionName])) {
            $faction = $this->factionRepository->findByOrCreate($factionName,$larp);
            $this->cache[$factionName] = $faction;
        }
        $character->addFaction($this->cache[$factionName]);
    }

    private function createReference(LarpCharacter $character, int|string $rowNo, SharedFile $file): void
    {
        $reference = new ExternalReference();
        $reference->setTargetType($character::getTargetType());
        $reference->setTargetId($character->getId());
        $reference->setProvider($file->getIntegration()->getProvider());
        $reference->setExternalId($rowNo + 1);
        $reference->setReferenceType(ReferenceType::SpreadsheetRow);
        $reference->setName($character->getTitle());
        $reference->setUrl($this->integrationService->createReferenceUrl($file, ReferenceType::SpreadsheetRow, $reference->getExternalId()));
        $reference->setRole(ReferenceRole::Primary);

        $this->entityManager->persist($reference);
    }
}
