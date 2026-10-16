<?php

namespace App\Domain\Map\EventListener;

use App\Domain\Core\Event\LarpDeletingEvent;
use App\Domain\Map\Repository\GameMapRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class RemoveMapImagesOfDeletedLarpListener
{
    public function __construct(
        private GameMapRepository $gameMapRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDirectory,
    ) {
    }

    public function __invoke(LarpDeletingEvent $event): void
    {
        foreach ($this->gameMapRepository->findBy(['larp' => $event->getLarp()]) as $gameMap) {
            $imageFile = $gameMap->getImageFile();
            if ($imageFile === null) {
                continue;
            }

            $event->removeAfterDeletion($this->projectDirectory . '/public/uploads/maps/' . basename($imageFile));
        }
    }
}
