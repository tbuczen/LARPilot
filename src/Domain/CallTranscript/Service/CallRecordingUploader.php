<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\Account\Entity\User;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Service\Import\CallRecordingImporterInterface;
use App\Domain\CallTranscript\Service\Speaker\CallSpeakerMatcher;
use App\Domain\Core\Entity\Larp;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class CallRecordingUploader
{
    public function __construct(
        private CallRecordingImporterInterface $importer,
        private CallRecordingStorage $storage,
        private EntityManagerInterface $entityManager,
        private CallSpeakerMatcher $speakerMatcher,
    ) {
    }

    public function upload(Larp $larp, User $organizer, string $title, ?string $language, string $archivePath): CallRecording
    {
        $recording = new CallRecording($larp, $title, $language);
        $recording->setCreatedBy($organizer);
        $this->entityManager->persist($recording);

        try {
            $importedRecording = $this->importer->import($archivePath, $this->storage->directoryFor($recording));
        } catch (Throwable $failure) {
            $this->storage->removeAll($recording);
            $this->entityManager->detach($recording);
            throw $failure;
        }

        $recording
            ->setRecordingStartedAt($importedRecording->startedAt)
            ->setNotes($importedRecording->notes);
        foreach ($importedRecording->tracks as $importedTrack) {
            $track = new CallRecordingTrack($recording, $importedTrack->speaker, $importedTrack->audioFilename, $importedTrack->transcriptFilename);
            $track->setDiscordUserId($importedTrack->discordUserId);
        }

        $this->speakerMatcher->proposeParticipants($recording);

        $this->entityManager->flush();

        return $recording;
    }
}
