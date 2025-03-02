<?php

namespace App\EventSubscriber;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('doctrine.event_subscriber')]
readonly class EntityCreatedBySubscriber implements EventSubscriber
{
    public function __construct(private Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof CreatorAwareInterface) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $entity->setCreatedBy($user);
            }
        }
    }
}
