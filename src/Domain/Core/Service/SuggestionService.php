<?php

namespace App\Domain\Core\Service;

use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\EventRepository;
use App\Domain\StoryObject\Repository\QuestRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;

readonly class SuggestionService
{
    public function __construct(
        private CharacterRepository $characterRepository,
        private QuestRepository $questRepository,
        //        private EventRepository $eventRepository,
        //        private ThreadRepository $threadRepository,
    ) {
    }

    /**
     * @return Character[]
     */
    public function suggestCharactersForQuest(Quest $quest): array
    {
        $tagIds = $this->collectTagIds($quest->getInvolvedCharacters());
        if ($tagIds === []) {
            return [];
        }

        $qb = $this->characterRepository->createQueryBuilder('c');
        $qb->innerJoin('c.tags', 't')
            ->andWhere('c.larp = :larp')
            ->andWhere('t.id IN (:tags)')
            ->setParameter('larp', $quest->getLarp())
            ->setParameter('tags', $tagIds)
            ->addOrderBy('c.title', 'ASC');

        if (!$quest->getInvolvedCharacters()->isEmpty()) {
            $qb->andWhere('c NOT IN (:involved)')
                ->setParameter('involved', $quest->getInvolvedCharacters());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Character[]
     */
    public function suggestCharactersForEvent(Event $event): array
    {
        $tagIds = $this->collectTagIds($event->getInvolvedCharacters());
        if ($tagIds === []) {
            return [];
        }

        $qb = $this->characterRepository->createQueryBuilder('c');
        $qb->innerJoin('c.tags', 't')
            ->andWhere('c.larp = :larp')
            ->andWhere('t.id IN (:tags)')
            ->setParameter('larp', $event->getLarp())
            ->setParameter('tags', $tagIds)
            ->addOrderBy('c.title', 'ASC');

        if (!$event->getInvolvedCharacters()->isEmpty()) {
            $qb->andWhere('c NOT IN (:involved)')
                ->setParameter('involved', $event->getInvolvedCharacters());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Character[]
     */
    public function suggestCharactersForThread(Thread $thread): array
    {
        $characters = $thread->getInvolvedCharacters()->toArray();
        foreach ($thread->getQuests() as $quest) {
            $characters = array_merge($characters, $quest->getInvolvedCharacters()->toArray());
        }
        foreach ($thread->getEvents() as $event) {
            $characters = array_merge($characters, $event->getInvolvedCharacters()->toArray());
        }

        $tagIds = $this->collectTagIds($characters);
        if ($tagIds === []) {
            return [];
        }

        $qb = $this->characterRepository->createQueryBuilder('c');
        $qb->innerJoin('c.tags', 't')
            ->andWhere('c.larp = :larp')
            ->andWhere('t.id IN (:tags)')
            ->setParameter('larp', $thread->getLarp())
            ->setParameter('tags', $tagIds)
            ->addOrderBy('c.title', 'ASC');

        if (!empty($characters)) {
            $qb->andWhere('c NOT IN (:involved)')
                ->setParameter('involved', $characters);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Quest[]
     */
    public function suggestQuestsForCharacter(Character $character): array
    {
        $tagIds = $this->collectTagIds([$character]);
        if ($tagIds === []) {
            return [];
        }

        $qb = $this->questRepository->createQueryBuilder('q');
        $qb->join('q.involvedCharacters', 'ic')
            ->join('ic.tags', 't')
            ->andWhere('q.larp = :larp')
            ->andWhere('t.id IN (:tags)')
            ->andWhere(':char NOT MEMBER OF q.involvedCharacters')
            ->setParameter('larp', $character->getLarp())
            ->setParameter('tags', $tagIds)
            ->setParameter('char', $character)
            ->addOrderBy('q.title', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param iterable<Character> $characters
     * @return string[]
     */
    private function collectTagIds(iterable $characters): array
    {
        $ids = [];
        foreach ($characters as $character) {
            foreach ($character->getTags() as $tag) {
                $ids[] = $tag->getId()->toRfc4122();
            }
        }

        return array_values(array_unique($ids));
    }
}
