<?php

namespace App\Domain\CallTranscript\MessageHandler;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Message\FinalizeCallTranscriptMessage;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Repository\CallRecordingTrackRepository;
use App\Domain\CallTranscript\Service\CallTranscriptPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FinalizeCallTranscriptHandler
{
    public function __construct(
        private CallRecordingRepository $recordingRepository,
        private CallRecordingTrackRepository $trackRepository,
        private CallTranscriptPublisher $publisher,
        private EntityManagerInterface $entityManager,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(FinalizeCallTranscriptMessage $message): void
    {
        $lock = $this->lockFactory->createLock('call_transcript.finalize.' . $message->recordingId->toRfc4122());
        $lock->acquire(true);

        try {
            $recording = $this->recordingRepository->find($message->recordingId);
            if (!$recording instanceof CallRecording) {
                return;
            }

            $this->entityManager->refresh($recording);
            if (!$recording->isTranscribing() || $this->trackRepository->countPending($recording) > 0) {
                return;
            }

            $this->publisher->publish($recording);
        } finally {
            $lock->release();
        }
    }
}
