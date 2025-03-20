<?php

namespace App\Domain\Larp\UseCase\ImportCharacters;

use App\Entity\Larp;
use App\Entity\LarpCharacter;
use App\Entity\LarpFaction;
use App\Repository\LarpRepository;
use App\Repository\LarpCharacterRepository;
use App\Repository\LarpFactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ImportCharactersHandler
{

    private array $cache;
    public function __construct(
        private readonly LarpRepository          $larpRepository,
        private readonly LarpCharacterRepository $characterRepository,
        private readonly LarpFactionRepository   $factionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(ImportCharactersCommand $command): void
    {
        $larp = $this->larpRepository->find($command->larpId);
        if (!$larp) {
            throw new Exception('Larp not found for the character import handler.');
        }

        $this->entityManager->beginTransaction();
        try {
            // Cache to store factions already found/created for this import
            foreach ($command->rows as $row) {
                if (!$this->validateRow($row, $command->mapping)) {
                    continue;
                }

                // Get required field: characterName
                $characterName = $this->getFieldValue($row, $command->mapping, 'characterName');
                if (!$characterName) {
                    continue; // Skip rows missing a character name.
                }

                // Check for duplicates based on character name & LARP.
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
                foreach ($command->mapping as $col => $fieldName) {
                    $value = $row[$col] ?? null;
                    if ($value === null && !$command->force) {
                        continue;
                    }

                    if ($fieldName === 'faction') {
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

                //TODO:: Save the connection between spreadsheet row and the $character
                $this->entityManager->persist($character);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $ex) {
            $this->entityManager->rollback();
            throw $ex;
        }
    }

    /**
     * Validate a row against the mapping.
     * For now, we require that the row provides a non-empty value for 'characterName'.
     */
    private function validateRow(array $row, array $mapping): bool
    {
        $characterName = $this->getFieldValue($row, $mapping, 'characterName');
        return !empty($characterName);
    }

    /**
     * Retrieves the value from a row for a given field as defined in the mapping.
     * Example: if mapping is ['B' => 'characterName', 'C' => 'faction'] and $field is 'characterName',
     * it returns $row['B'] if present.
     */
    private function getFieldValue(array $row, array $mapping, string $field): ?string
    {
        foreach ($mapping as $col => $mappedField) {
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
            $faction = $this->factionRepository->findByOrCreate([
                'name' => $factionName,
                'larp' => $larp,
            ]);

            $faction->setName($factionName);
            $faction->addLarp($larp);
            $this->cache[$factionName] = $faction;
        }
        $character->addFaction($this->cache[$factionName]);
    }
}
