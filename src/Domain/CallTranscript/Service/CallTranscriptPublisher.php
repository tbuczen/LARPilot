<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\DTO\SpeakerTranscript;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Service\Transcript\TranscriptMerger;
use App\Domain\Core\Service\Helper\DateFormat;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CallTranscriptPublisher
{
    public const KNOWLEDGE_DOCUMENT_CATEGORY = 'call_transcript';

    public function __construct(
        private TranscriptMerger $merger,
        private CallRecordingStorage $storage,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function publish(CallRecording $recording): void
    {
        $speakerTranscripts = [];
        $failedSpeakers = [];
        foreach ($recording->getTracks() as $track) {
            if (!$track->isTranscribed()) {
                $failedSpeakers[] = $this->describeFailure($track);
                continue;
            }

            $speakerTranscripts[] = new SpeakerTranscript($track->getSpeaker(), $track->getSegments());
        }

        $this->storage->removeAll($recording);

        if ($speakerTranscripts === []) {
            $recording->fail(implode("\n", $failedSpeakers));
            $this->entityManager->flush();

            return;
        }

        $noteSpeaker = $this->translator->trans('call_recording.note_speaker', [], 'call_transcript');
        $speakerTranscripts[] = new SpeakerTranscript($noteSpeaker, $recording->getNotes());

        $knowledgeDocument = new KnowledgeDocument();
        $knowledgeDocument
            ->setLarp($recording->getLarp())
            ->setTitle($recording->getTitle())
            ->setCategory(self::KNOWLEDGE_DOCUMENT_CATEGORY)
            ->setIsPublic(false)
            ->setContent($this->renderContent($recording, $this->merger->merge($speakerTranscripts)));
        $creator = $recording->getCreatedBy();
        if ($creator !== null) {
            $knowledgeDocument->setCreatedBy($creator);
        }

        $this->entityManager->persist($knowledgeDocument);
        $recording->complete($knowledgeDocument, $failedSpeakers === [] ? null : implode("\n", $failedSpeakers));
        $this->entityManager->flush();
    }

    /**
     * @param list<string> $lines
     */
    private function renderContent(CallRecording $recording, array $lines): string
    {
        $paragraphs = [];
        $startedAt = $recording->getRecordingStartedAt();
        if ($startedAt !== null) {
            $paragraphs[] = $this->translator->trans('call_recording.recorded_at', [
                '%date%' => $startedAt->setTimezone(new DateTimeZone(date_default_timezone_get()))->format(DateFormat::DATETIME),
            ], 'call_transcript');
        }

        foreach ($lines as $line) {
            $paragraphs[] = $line;
        }

        return implode('', array_map(
            static fn (string $paragraph): string => '<p>' . htmlspecialchars($paragraph, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>',
            $paragraphs,
        ));
    }

    private function describeFailure(CallRecordingTrack $track): string
    {
        return sprintf('%s: %s', $track->getSpeaker(), $track->getFailureReason() ?? '-');
    }
}
