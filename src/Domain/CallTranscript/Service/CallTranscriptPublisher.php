<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CallTranscriptPublisher
{
    public const KNOWLEDGE_DOCUMENT_CATEGORY = 'call_transcript';

    public function __construct(
        private CallRecordingStorage $storage,
        private EntityManagerInterface $entityManager,
        private CallTranscriptDocumentRenderer $documentRenderer,
        private CallTranscriptOutputDispatcher $outputDispatcher,
    ) {
    }

    public function publish(CallRecording $recording): void
    {
        $failedSpeakers = [];
        $transcribedTracks = 0;
        foreach ($recording->getTracks() as $track) {
            if (!$track->isTranscribed()) {
                $failedSpeakers[] = $this->describeFailure($track);
                continue;
            }

            ++$transcribedTracks;
        }

        $this->storage->removeAll($recording);

        if ($transcribedTracks === 0) {
            $recording->fail(implode("\n", $failedSpeakers));
            $this->entityManager->flush();

            return;
        }

        $knowledgeDocument = new KnowledgeDocument();
        $knowledgeDocument
            ->setLarp($recording->getLarp())
            ->setTitle($recording->getTitle())
            ->setCategory(self::KNOWLEDGE_DOCUMENT_CATEGORY)
            ->setIsPublic(false)
            ->setContent($this->documentRenderer->render($recording));
        $creator = $recording->getCreatedBy();
        if ($creator !== null) {
            $knowledgeDocument->setCreatedBy($creator);
        }

        $this->entityManager->persist($knowledgeDocument);
        $recording->complete($knowledgeDocument, $failedSpeakers === [] ? null : implode("\n", $failedSpeakers));
        $this->entityManager->flush();

        $this->outputDispatcher->afterTranscript($recording);
    }

    private function describeFailure(CallRecordingTrack $track): string
    {
        return sprintf('%s: %s', $track->getSpeaker(), $track->getFailureReason() ?? '-');
    }
}
