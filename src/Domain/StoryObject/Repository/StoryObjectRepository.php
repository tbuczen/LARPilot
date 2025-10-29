<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends BaseRepository<StoryObject>
 *
 * @method null|StoryObject find($id, $lockMode = null, $lockVersion = null)
 * @method null|StoryObject findOneBy(array $criteria, array $orderBy = null)
 * @method StoryObject[]    findAll()
 * @method StoryObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoryObjectRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryObject::class);
    }

    /**
     * @return StoryObject[]
     */
    public function searchByTitle(Larp $larp, string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.larp = :larp')
            ->andWhere('LOWER(o.title) LIKE :q')
            ->setParameter('larp', $larp)
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Larp                $larp
     * @param Collection<int, Thread>|Thread[]       $threads
     * @param Collection<int, Character>|Character[] $characters
     * @param Collection<int, Faction>|Faction[]     $factions
     * @return array{threads: Thread[], characters: Character[], factions: Faction[], quests: Quest[]}
     */
    public function findForGraph(
        Larp $larp,
        iterable $threads = [],
        iterable $characters = [],
        iterable $factions = [],
    ): array {
        // Normalize input IDs
        $inputThreadIds = $this->normalizeIds($threads);
        $inputCharacterIds = $this->normalizeIds($characters);
        $inputFactionIds = $this->normalizeIds($factions);
        
        // Determine which types need filtering
        $hasThreadFilter = $inputThreadIds !== [];
        $hasCharacterFilter = $inputCharacterIds !== [];
        $hasFactionFilter = $inputFactionIds !== [];
        $hasAnyFilter = $hasThreadFilter || $hasCharacterFilter || $hasFactionFilter;
        
        // If no filters, return all entities with preloading
        if (!$hasAnyFilter) {
            return $this->fetchAllEntitiesGrouped($larp);
        }
        
        // Collect all connected IDs for relation lookup
        $allIds = array_merge($inputThreadIds, $inputCharacterIds, $inputFactionIds);
        
        // Add connected objects for each selected thread
        foreach ($inputThreadIds as $threadId) {
            $allIds = array_merge($allIds, $this->getConnectedToThread($larp, $threadId));
        }
        
        // Add connected objects for each selected character
        foreach ($inputCharacterIds as $characterId) {
            $allIds = array_merge($allIds, $this->getConnectedToCharacter($larp, $characterId));
        }
        
        // Add connected objects for each selected faction
        foreach ($inputFactionIds as $factionId) {
            $allIds = array_merge($allIds, $this->getConnectedToFaction($larp, $factionId));
        }
        
        $allIds = array_unique($allIds);
        
        // Add IDs from Relations (source and target)
        $relationIds = $this->getRelatedObjectIds($larp, $allIds);
        $allIds = array_merge($allIds, $relationIds);
        $allIds = array_unique($allIds);

        // Separate IDs by type
        $allThreadIds = $this->findIdsByType($larp, Thread::class, $allIds);
        $allCharacterIds = $this->findIdsByType($larp, Character::class, $allIds);
        $allFactionIds = $this->findIdsByType($larp, Faction::class, $allIds);
        $allQuestIds = $this->findIdsByType($larp, Quest::class, $allIds);
        
        // Fetch entities (excluding input) or all if no filter for that type
        $threadIdsToFetch = $hasThreadFilter ? array_diff($allThreadIds, $inputThreadIds) : $allThreadIds;
        $characterIdsToFetch = $hasCharacterFilter ? array_diff($allCharacterIds, $inputCharacterIds) : $allCharacterIds;
        $factionIdsToFetch = $hasFactionFilter ? array_diff($allFactionIds, $inputFactionIds) : $allFactionIds;
        $questIdsToFetch = $allQuestIds; // Always fetch all connected quests

        return $this->fetchEntitiesGrouped($larp, $threadIdsToFetch, $characterIdsToFetch, $factionIdsToFetch, $questIdsToFetch);
    }

    /**
     * @param iterable $values
     * @return string[]
     */
    private function normalizeIds(iterable $values): array
    {
        $ids = [];
        foreach ($values as $v) {
            if ($v instanceof Uuid) {
                $ids[] = $v->toRfc4122();
            } elseif (method_exists($v, 'getId')) {
                $ids[] = $v->getId()->toRfc4122();
            } elseif (is_string($v)) {
                $ids[] = $v;
            }
        }
        return array_unique($ids);
    }

    /**
     * @param string $dql
     * @param array $parameters
     * @return string[]
     */
    private function fetchIds(string $dql, array $parameters): array
    {
        $rows = $this->getEntityManager()->createQuery($dql)
            ->setParameters($parameters)
            ->getSingleColumnResult();

        return array_map(static fn ($id): string => $id instanceof Uuid ? $id->toRfc4122() : (string) $id, $rows);
    }

    /**
     * @param array<int, array<string>> $sets
     * @return string[]
     */
    private function intersectSets(array $sets): array
    {
        if ($sets === []) {
            return [];
        }

        $base = array_shift($sets);
        foreach ($sets as $set) {
            $base = array_intersect($base, $set);
        }

        return array_values(array_unique($base));
    }

    private function getConnectedToThread(Larp $larp, string $threadId): array
    {
        $ids = [];
    
        // Get quests and events for this thread
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT q.id FROM ' . Quest::class . ' q WHERE q.larp = :larp AND q.thread = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT e.id FROM ' . Event::class . ' e WHERE e.larp = :larp AND e.thread = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        // Get involved characters and factions
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT c.id FROM ' . Character::class . ' c JOIN c.threads t WHERE c.larp = :larp AND t.id = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT f.id FROM ' . Faction::class . ' f JOIN f.threads t WHERE f.larp = :larp AND t.id = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        return $ids;
    }

    private function getConnectedToCharacter(Larp $larp, string $characterId): array
    {
        $ids = [];
    
        // Get character's factions
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT f.id FROM ' . Faction::class . ' f JOIN f.members c WHERE f.larp = :larp AND c.id = :characterId',
            ['larp' => $larp, 'characterId' => $characterId]
        ));
    
        // Get character's threads, quests, events
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT t.id FROM ' . Thread::class . ' t JOIN t.involvedCharacters c WHERE t.larp = :larp AND c.id = :characterId',
            ['larp' => $larp, 'characterId' => $characterId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT q.id FROM ' . Quest::class . ' q JOIN q.involvedCharacters c WHERE q.larp = :larp AND c.id = :characterId',
            ['larp' => $larp, 'characterId' => $characterId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT e.id FROM ' . Event::class . ' e JOIN e.involvedCharacters c WHERE e.larp = :larp AND c.id = :characterId',
            ['larp' => $larp, 'characterId' => $characterId]
        ));
    
        return $ids;
    }

    private function getConnectedToFaction(Larp $larp, string $factionId): array
    {
        $ids = [];
    
        // Get faction members
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT c.id FROM ' . Character::class . ' c JOIN c.factions f WHERE c.larp = :larp AND f.id = :factionId',
            ['larp' => $larp, 'factionId' => $factionId]
        ));
    
        // Get faction's threads, quests, events
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT t.id FROM ' . Thread::class . ' t JOIN t.involvedFactions f WHERE t.larp = :larp AND f.id = :factionId',
            ['larp' => $larp, 'factionId' => $factionId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT q.id FROM ' . Quest::class . ' q JOIN q.involvedFactions f WHERE q.larp = :larp AND f.id = :factionId',
            ['larp' => $larp, 'factionId' => $factionId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT e.id FROM ' . Event::class . ' e JOIN e.involvedFactions f WHERE e.larp = :larp AND f.id = :factionId',
            ['larp' => $larp, 'factionId' => $factionId]
        ));
    
        return $ids;
    }

    /**
     * Fetch all entities grouped by type with preloading.
     *
     * @return array{threads: Thread[], characters: Character[], factions: Faction[], quests: Quest[]}
     */
    private function fetchAllEntitiesGrouped(Larp $larp): array
    {
        $threads = $this->fetchThreads($larp);
        $characters = $this->fetchCharacters($larp);
        $factions = $this->fetchFactions($larp);
        $quests = $this->fetchQuests($larp);
        
        // TODO: Add entity preloader here
        // $this->entityPreloader->preload($characters, ['factions', 'threads', 'quests']);
        // $this->entityPreloader->preload($threads, ['involvedCharacters', 'involvedFactions']);
        // $this->entityPreloader->preload($quests, ['involvedCharacters', 'involvedFactions', 'thread']);
        // $this->entityPreloader->preload($factions, ['members']);
        return [
            'threads' => $threads,
            'characters' => $characters,
            'factions' => $factions,
            'quests' => $quests,
        ];
    }

    /**
     * Fetch specific entities grouped by type with preloading.
     *
     * @param array<string> $threadIds
     * @param array<string> $characterIds
     * @param array<string> $factionIds
     * @param array<string> $questIds
     * @return array{threads: Thread[], characters: Character[], factions: Faction[], quests: Quest[]}
     */
    private function fetchEntitiesGrouped(
        Larp $larp,
        array $threadIds,
        array $characterIds,
        array $factionIds,
        array $questIds
    ): array {
        $threads = $this->fetchThreadsByIds($larp, $threadIds);
        $characters = $this->fetchCharactersByIds($larp, $characterIds);
        $factions = $this->fetchFactionsByIds($larp, $factionIds);
        $quests = $this->fetchQuestsByIds($larp, $questIds);
        
        // TODO: Add entity preloader here
        // $this->entityPreloader->preload($characters, ['factions', 'threads', 'quests']);
        // $this->entityPreloader->preload($threads, ['involvedCharacters', 'involvedFactions']);
        // $this->entityPreloader->preload($quests, ['involvedCharacters', 'involvedFactions', 'thread']);
        // $this->entityPreloader->preload($factions, ['members']);
        
        return [
            'threads' => $threads,
            'characters' => $characters,
            'factions' => $factions,
            'quests' => $quests,
        ];
    }

    /**
     * @return Thread[]
     */
    private function fetchThreads(Larp $larp): array
    {
        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Thread::class)
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string> $ids
     * @return Thread[]
     */
    private function fetchThreadsByIds(Larp $larp, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Thread::class)
            ->andWhere('so.id IN (:ids)')
            ->setParameter('larp', $larp)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Character[]
     */
    private function fetchCharacters(Larp $larp): array
    {
        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Character::class)
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string> $ids
     * @return Character[]
     */
    private function fetchCharactersByIds(Larp $larp, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Character::class)
            ->andWhere('so.id IN (:ids)')
            ->setParameter('larp', $larp)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Faction[]
     */
    private function fetchFactions(Larp $larp): array
    {
        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Faction::class)
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string> $ids
     * @return Faction[]
     */
    private function fetchFactionsByIds(Larp $larp, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Faction::class)
            ->andWhere('so.id IN (:ids)')
            ->setParameter('larp', $larp)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Quest[]
     */
    private function fetchQuests(Larp $larp): array
    {
        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF :type')
            ->setParameter('larp', $larp)
            ->setParameter('type', Quest::class)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string> $ids
     * @return Quest[]
     */
    private function fetchQuestsByIds(Larp $larp, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so INSTANCE OF ' . Quest::class)
            ->andWhere('so.id IN (:ids)')
            ->setParameter('larp', $larp)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get IDs of a specific type from a list of mixed IDs.
     *
     * @template T of StoryObject
     * @param class-string<T> $type
     * @param array<string> $ids
     * @return array<string>
     */
    private function findIdsByType(Larp $larp, string $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $dql = 'SELECT so.id FROM ' . StoryObject::class . ' so 
                WHERE so.larp = :larp 
                AND so INSTANCE OF :type 
                AND so.id IN (:ids)';

        $rows = $this->getEntityManager()->createQuery($dql)
            ->setParameter('larp', $larp)
            ->setParameter('type', $type)
            ->setParameter('ids', $ids)
            ->getSingleColumnResult();

        return array_map(
            static fn ($id): string => $id instanceof Uuid ? $id->toRfc4122() : (string) $id,
            $rows
        );
    }

    /**
     * Get IDs of story objects referenced in Relations.
     *
     * @param Larp $larp
     * @param array<string> $objectIds
     * @return array<string>
     */
    private function getRelatedObjectIds(Larp $larp, array $objectIds): array
    {
        if ($objectIds === []) {
            return [];
        }
        
        // Get all Relations where from or to is in our object IDs
        $dql = 'SELECT IDENTITY(r.from) as fromId, IDENTITY(r.to) as toId 
                FROM ' . Relation::class . ' r 
                WHERE r.larp = :larp 
                AND (r.from IN (:ids) OR r.to IN (:ids))';
        
        $rows = $this->getEntityManager()->createQuery($dql)
            ->setParameter('larp', $larp)
            ->setParameter('ids', $objectIds)
            ->getResult();
        
        $relatedIds = [];
        foreach ($rows as $row) {
            if ($row['fromId'] !== null) {
                $id = $row['fromId'] instanceof Uuid ? $row['fromId']->toRfc4122() : (string) $row['fromId'];
                $relatedIds[] = $id;
            }
            if ($row['toId'] !== null) {
                $id = $row['toId'] instanceof Uuid ? $row['toId']->toRfc4122() : (string) $row['toId'];
                $relatedIds[] = $id;
            }
        }
        
        return array_unique($relatedIds);
    }
}
