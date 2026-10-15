<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Message\TranscribeCallTrackMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CallTranscriptionStarter
{
    public function __construct(
        private CallRecordingStorage $storage,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param list<string> $includedTrackIds
     */
    public function start(CallRecording $recording, array $includedTrackIds): int
    {
        $includedTracks = [];
        $excludedTracks = [];
        foreach ($recording->getTracks() as $track) {
            if (in_array($track->getId()->toRfc4122(), $includedTrackIds, true)) {
                $includedTracks[] = $track;
                continue;
            }

            $excludedTracks[] = $track;
        }

        if ($includedTracks === []) {
            return 0;
        }

        foreach ($excludedTracks as $track) {
            $this->storage->removeFile($recording, $track->getAudioFilename());
            $this->storage->removeFile($recording, $track->getTranscriptFilename());
            $recording->removeTrack($track);
        }

        $recording->startTranscription();
        $this->entityManager->flush();

        foreach ($includedTracks as $track) {
            $this->messageBus->dispatch(new TranscribeCallTrackMessage($track->getId()));
        }

        return count($includedTracks);
    }

    public function discard(CallRecording $recording): void
    {
        $this->storage->removeAll($recording);
        $this->entityManager->remove($recording);
        $this->entityManager->flush();
    }
}
