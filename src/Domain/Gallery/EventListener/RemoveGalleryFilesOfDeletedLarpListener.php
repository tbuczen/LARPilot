<?php

namespace App\Domain\Gallery\EventListener;

use App\Domain\Core\Event\LarpDeletingEvent;
use App\Domain\Gallery\Repository\GalleryRepository;
use App\Domain\Gallery\Service\GalleryFileService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class RemoveGalleryFilesOfDeletedLarpListener
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private GalleryFileService $galleryFileService,
    ) {
    }

    public function __invoke(LarpDeletingEvent $event): void
    {
        foreach ($this->galleryRepository->findBy(['larp' => $event->getLarp()]) as $gallery) {
            $event->removeAfterDeletion($this->galleryFileService->getGalleryUploadDirectory($gallery));
        }
    }
}
