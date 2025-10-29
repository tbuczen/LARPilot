<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Event>
 *
 * @method null|Event find($id, $lockMode = null, $lockVersion = null)
 * @method null|Event findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Create a query builder for events filtered by tags.
     *
     * @param Tag[] $tags
     */
    public function createEventsByTagsQueryBuilder(Larp $larp, array $tags): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e');
        $qb->join('e.tags', 'tag')
            ->where('e.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags)
            ->groupBy('e.id')
            ->orderBy('COUNT(tag.id)', 'DESC');

        return $qb;
    }

    /**
     * Create a query builder for timeline events visible to a participant.
     *
     * Visibility rules:
     * - Public events (no involvedCharacters and no involvedFactions) are visible to all
     * - Events with involvedCharacters are visible to those specific characters
     * - Events with involvedFactions are visible to all characters in those factions
     * - Story writers can see all events
     */
    public function createTimelineQueryBuilder(Larp $larp, ?LarpParticipant $participant = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp);

        // If no participant provided, return empty result (for security)
        if (!$participant) {
            $qb->andWhere('1 = 0');
            return $qb;
        }

        // Story writers can see all events
        if ($participant->isStoryWriter() || $participant->isOrganizer()) {
            return $qb;
        }

        // For players, apply visibility filters
        $characters = $participant->getLarpCharacters();

        if ($characters->isEmpty()) {
            // No characters = can only see public events
            $qb->leftJoin('e.involvedCharacters', 'ic')
                ->leftJoin('e.involvedFactions', 'if')
                ->andWhere('ic.id IS NULL')
                ->andWhere('if.id IS NULL');
        } else {
            // Get all faction IDs for participant's characters
            $factionIds = [];
            foreach ($characters as $character) {
                foreach ($character->getFactions() as $faction) {
                    $factionIds[] = $faction->getId();
                }
            }

            // Event is visible if:
            // 1. It's public (no involved characters/factions), OR
            // 2. Participant's character is in involvedCharacters, OR
            // 3. Participant's character's faction is in involvedFactions
            $qb->leftJoin('e.involvedCharacters', 'ic')
                ->leftJoin('e.involvedFactions', 'if')
                ->andWhere('
                    (ic.id IS NULL AND if.id IS NULL) OR
                    ic IN (:characters) OR
                    if.id IN (:factions)
                ')
                ->setParameter('characters', $characters)
                ->setParameter('factions', $factionIds);
        }

        return $qb;
    }

    /**
     * Find all events visible to a specific character.
     *
     * @return Event[]
     */
    public function findVisibleToCharacter(Character $character): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $character->getLarp());

        // Get faction IDs
        $factionIds = [];
        foreach ($character->getFactions() as $faction) {
            $factionIds[] = $faction->getId();
        }

        // Event is visible if public, character is involved, or character's faction is involved
        $qb->leftJoin('e.involvedCharacters', 'ic')
            ->leftJoin('e.involvedFactions', 'if')
            ->andWhere('
                (ic.id IS NULL AND if.id IS NULL) OR
                ic = :character OR
                if.id IN (:factions)
            ')
            ->setParameter('character', $character)
            ->setParameter('factions', $factionIds ?: [0]); // Use [0] if empty to avoid SQL error

        return $qb->getQuery()->getResult();
    }
}
