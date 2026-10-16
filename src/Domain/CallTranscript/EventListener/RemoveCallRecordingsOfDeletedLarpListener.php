<?php

namespace App\Domain\CallTranscript\EventListener;

use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Service\CallRecordingStorage;
use App\Domain\Core\Event\LarpDeletingEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class RemoveCallRecordingsOfDeletedLarpListener
{
    public function __construct(
        private CallRecordingRepository $callRecordingRepository,
        private CallRecordingStorage $callRecordingStorage,
    ) {
    }

    public function __invoke(LarpDeletingEvent $event): void
    {
        foreach ($this->callRecordingRepository->findBy(['larp' => $event->getLarp()]) as $callRecording) {
            $event->removeAfterDeletion($this->callRecordingStorage->recordingDirectory($callRecording));
        }
    }
}
