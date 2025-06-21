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
        $sets = [];

        $idsForThreads = [];
        $threadIds = $this->normalizeIds($threads);
        if ($threadIds !== []) {
            // thread itself
            $idsForThreads = array_merge($idsForThreads, $threadIds);
            // quests
            $idsForThreads = array_merge($idsForThreads, $this->fetchIds(
                'SELECT q.id FROM ' . Quest::class . ' q JOIN q.thread t WHERE q.larp = :larp AND t.id IN (:ids)',
                ['larp' => $larp, 'ids' => $threadIds]
            ));
            // events
            $idsForThreads = array_merge($idsForThreads, $this->fetchIds(
                'SELECT e.id FROM ' . Event::class . ' e JOIN e.thread t WHERE e.larp = :larp AND t.id IN (:ids)',
                ['larp' => $larp, 'ids' => $threadIds]
            ));
            // involved characters
            $idsForThreads = array_merge($idsForThreads, $this->fetchIds(
                'SELECT c.id FROM ' . LarpCharacter::class . ' c JOIN c.threads t WHERE c.larp = :larp AND t.id IN (:ids)',
                ['larp' => $larp, 'ids' => $threadIds]
            ));
            // involved factions
            $idsForThreads = array_merge($idsForThreads, $this->fetchIds(
                'SELECT f.id FROM ' . LarpFaction::class . ' f JOIN f.threads t WHERE f.larp = :larp AND t.id IN (:ids)',
                ['larp' => $larp, 'ids' => $threadIds]
            ));
            $sets[] = array_unique($idsForThreads);
        }

        $factionIds = $this->normalizeIds($factions);
        if ($factionIds !== []) {
            $factionSets = [];
            foreach ($factionIds as $fid) {
                $ids = [$fid];
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT c.id FROM ' . LarpCharacter::class . ' c JOIN c.factions f WHERE c.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT t.id FROM ' . Thread::class . ' t JOIN t.involvedFactions f WHERE t.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT q.id FROM ' . Quest::class . ' q JOIN q.involvedFactions f WHERE q.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT e.id FROM ' . Event::class . ' e JOIN e.involvedFactions f WHERE e.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                // include objects linked through faction members
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT t.id FROM ' . Thread::class . ' t JOIN t.involvedCharacters c JOIN c.factions f WHERE t.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT q.id FROM ' . Quest::class . ' q JOIN q.involvedCharacters c JOIN c.factions f WHERE q.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT e2.id FROM ' . Event::class . ' e2 JOIN e2.involvedCharacters c JOIN c.factions f WHERE e2.larp = :larp AND f.id = :f',
                    ['larp' => $larp, 'f' => $fid]
                ));
                $factionSets[] = array_unique($ids);
            }
            $sets[] = $this->intersectSets($factionSets);
        }

        $characterIds = $this->normalizeIds($characters);
        if ($characterIds !== []) {
            $charSets = [];
            foreach ($characterIds as $cid) {
                $ids = [$cid];
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT t.id FROM ' . Thread::class . ' t JOIN t.involvedCharacters c WHERE t.larp = :larp AND c.id = :c',
                    ['larp' => $larp, 'c' => $cid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT q.id FROM ' . Quest::class . ' q JOIN q.involvedCharacters c WHERE q.larp = :larp AND c.id = :c',
                    ['larp' => $larp, 'c' => $cid]
                ));
                $ids = array_merge($ids, $this->fetchIds(
                    'SELECT e.id FROM ' . Event::class . ' e JOIN e.involvedCharacters c WHERE e.larp = :larp AND c.id = :c',
                    ['larp' => $larp, 'c' => $cid]
                ));
                $charSets[] = array_unique($ids);
            }
            $sets[] = $this->intersectSets($charSets);
        }

        if ($sets === []) {
            $qb = $this->createQueryBuilder('so');
            $qb->where('so.larp = :larp')
                ->andWhere('so NOT INSTANCE OF ' . Relation::class)
                ->setParameter('larp', $larp);

            return $qb->getQuery()->getResult();
        }

        $ids = $this->intersectSets($sets);
        if ($ids === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('so');
        $qb->where('so.larp = :larp')
            ->andWhere('so.id IN (:ids)')
            ->andWhere('so NOT INSTANCE OF ' . Relation::class)
            ->setParameter('larp', $larp)
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
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

        return array_map(static fn ($id) => $id instanceof Uuid ? $id->toRfc4122() : (string) $id, $rows);
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
}
