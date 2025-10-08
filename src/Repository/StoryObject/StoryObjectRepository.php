<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Relation;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<StoryObject>
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
     * @param Collection<int, LarpCharacter>|LarpCharacter[] $characters
     * @param Collection<int, LarpFaction>|LarpFaction[]     $factions
     * @return StoryObject[]
     */
    public function findForGraph(
        Larp $larp,
        iterable $threads = [],
        iterable $characters = [],
        iterable $factions = [],
    ): array {
        $allIds = [];
        
        // Get base filtered objects
        $threadIds = $this->normalizeIds($threads);
        $characterIds = $this->normalizeIds($characters);
        $factionIds = $this->normalizeIds($factions);
        
        // If no filters, return all objects
        if ($threadIds === [] && $characterIds === [] && $factionIds === []) {
            return $this->createQueryBuilder('so')
                ->where('so.larp = :larp')
                ->andWhere('so NOT INSTANCE OF ' . Relation::class)
                ->setParameter('larp', $larp)
                ->getQuery()
                ->getResult();
        }
        
        // Add directly selected objects
        $allIds = array_merge($allIds, $threadIds, $characterIds, $factionIds);
        
        // Add connected objects for each selected thread
        foreach ($threadIds as $threadId) {
            $allIds = array_merge($allIds, $this->getConnectedToThread($larp, $threadId));
        }
        
        // Add connected objects for each selected character
        foreach ($characterIds as $characterId) {
            $allIds = array_merge($allIds, $this->getConnectedToCharacter($larp, $characterId));
        }
        
        // Add connected objects for each selected faction
        foreach ($factionIds as $factionId) {
            $allIds = array_merge($allIds, $this->getConnectedToFaction($larp, $factionId));
        }
        
        $allIds = array_unique($allIds);
        
        if ($allIds === []) {
            return [];
        }
        
        return $this->createQueryBuilder('so')
            ->where('so.larp = :larp')
            ->andWhere('so.id IN (:ids)')
            ->andWhere('so NOT INSTANCE OF ' . Relation::class)
            ->setParameter('larp', $larp)
            ->setParameter('ids', $allIds)
            ->getQuery()
            ->getResult();
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
            'SELECT c.id FROM ' . LarpCharacter::class . ' c JOIN c.threads t WHERE c.larp = :larp AND t.id = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT f.id FROM ' . LarpFaction::class . ' f JOIN f.threads t WHERE f.larp = :larp AND t.id = :threadId',
            ['larp' => $larp, 'threadId' => $threadId]
        ));
    
        return $ids;
    }

    private function getConnectedToCharacter(Larp $larp, string $characterId): array
    {
        $ids = [];
    
        // Get character's factions
        $ids = array_merge($ids, $this->fetchIds(
            'SELECT f.id FROM ' . LarpFaction::class . ' f JOIN f.members c WHERE f.larp = :larp AND c.id = :characterId',
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
            'SELECT c.id FROM ' . LarpCharacter::class . ' c JOIN c.factions f WHERE c.larp = :larp AND f.id = :factionId',
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
}
