<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
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
     * @param array<string, string> $participantIdsByTrackId
     */
    public function start(CallRecording $recording, array $includedTrackIds, array $participantIdsByTrackId = []): int
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

        $this->assignParticipants($recording, $includedTracks, $participantIdsByTrackId);

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

    /**
     * @param list<CallRecordingTrack> $tracks
     * @param array<string, string> $participantIdsByTrackId
     */
    private function assignParticipants(CallRecording $recording, array $tracks, array $participantIdsByTrackId): void
    {
        $participantsById = [];
        foreach ($recording->getLarp()?->getParticipants() ?? [] as $participant) {
            $participantsById[$participant->getId()->toRfc4122()] = $participant;
        }

        foreach ($tracks as $track) {
            $trackId = $track->getId()->toRfc4122();
            if (!array_key_exists($trackId, $participantIdsByTrackId)) {
                continue;
            }

            $track->setParticipant($participantsById[$participantIdsByTrackId[$trackId]] ?? null);
        }
    }

    public function discard(CallRecording $recording): void
    {
        $this->storage->removeAll($recording);
        $this->entityManager->remove($recording);
        $this->entityManager->flush();
    }
}
