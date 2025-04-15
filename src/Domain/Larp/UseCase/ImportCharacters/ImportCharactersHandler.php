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
use Webmozart\Assert\Assert;

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
        Assert::notNull($larp, sprintf('LARP %s not found for the character import handler.', $command->larpId));
        $columnMap = $command->mapping['columnMappings'];
        dump($columnMap);
        $this->entityManager->beginTransaction();
        try {
            // Cache to store factions already found/created for this import
            foreach ($command->rows as $rowNo => $row) {
                if($rowNo < $command->mapping['startingRow'] - 1) {
                    continue;
                }
                $characterName = $this->getFieldValue($row, $columnMap, 'characterName');
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
                foreach ($columnMap as $fieldName => $col) {
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

                //TODO:: Save the connection between spreadsheet row and the $character - we would need an entity for maintaning this conenctions - one character for example can have one row in character list, can have one dedicated thread on discord and one google document describing him.
                $this->entityManager->persist($character);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $ex) {
            $this->entityManager->rollback();
            throw $ex;
        }
        die;
    }

    /**
     * Retrieves the value from a row for a given field as defined in the mapping.
     * Example: if mapping is ['B' => 'characterName', 'C' => 'faction'] and $field is 'characterName',
     * it returns $row['B'] if present.
     */
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
