<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\EventSubscriber;

use App\Domain\StoryAI\Message\IndexStoryObjectMessage;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Automatically triggers indexing when story objects are created or updated.
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class StoryObjectIndexSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%env(bool:STORY_AI_AUTO_INDEX)%')]
        private readonly bool $autoIndexEnabled = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof StoryObject) {
            return;
        }

        if (!$this->autoIndexEnabled) {
            $this->logger?->debug('Auto-indexing disabled, skipping', [
                'story_object_id' => $entity->getId()->toRfc4122(),
            ]);
            return;
        }

        $this->dispatchIndexMessage($entity);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof StoryObject) {
            return;
        }

        if (!$this->autoIndexEnabled) {
            return;
        }

        $this->dispatchIndexMessage($entity);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        // Note: Embedding will be cascade-deleted via FK constraint
        // This listener is for logging purposes only
        $entity = $args->getObject();

        if (!$entity instanceof StoryObject) {
            return;
        }

        $this->logger?->debug('Story object removed, embedding will be cascade-deleted', [
            'story_object_id' => $entity->getId()->toRfc4122(),
        ]);
    }

    private function dispatchIndexMessage(StoryObject $storyObject): void
    {
        $larp = $storyObject->getLarp();
        if (!$larp) {
            $this->logger?->warning('Story object has no LARP, skipping indexing', [
                'story_object_id' => $storyObject->getId()->toRfc4122(),
            ]);
            return;
        }

        $this->logger?->debug('Dispatching index message', [
            'story_object_id' => $storyObject->getId()->toRfc4122(),
        ]);

        $this->messageBus->dispatch(
            new IndexStoryObjectMessage($storyObject->getId())
        );
    }
}
