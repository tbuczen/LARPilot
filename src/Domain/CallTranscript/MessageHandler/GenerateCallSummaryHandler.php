<?php

namespace App\Domain\CallTranscript\MessageHandler;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Message\GenerateCallSummaryMessage;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Service\CallTranscriptDocumentRenderer;
use App\Domain\CallTranscript\Service\CallTranscriptOutputDispatcher;
use App\Domain\CallTranscript\Service\Summary\CallSummaryException;
use App\Domain\CallTranscript\Service\Summary\CallSummaryGenerator;
use App\Domain\CallTranscript\Service\Summary\MentionedStoryObjectFinder;
use App\Domain\CallTranscript\Service\Transcript\CallTranscriptLines;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateCallSummaryHandler
{
    public function __construct(
        private CallRecordingRepository $recordingRepository,
        private CallTranscriptLines $transcriptLines,
        private CallSummaryGenerator $summaryGenerator,
        private MentionedStoryObjectFinder $storyObjectFinder,
        private CallTranscriptDocumentRenderer $documentRenderer,
        private CallTranscriptOutputDispatcher $outputDispatcher,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateCallSummaryMessage $message): void
    {
        $recording = $this->recordingRepository->find($message->recordingId);
        if (!$recording instanceof CallRecording || !$recording->isCompleted() || $recording->getSummaryStatus() !== CallOutputStatus::PENDING) {
            return;
        }

        $larp = $recording->getLarp();
        if (!$larp instanceof Larp) {
            return;
        }

        $lines = $this->transcriptLines->build($recording);
        try {
            $summary = $this->summaryGenerator->generate(
                $lines,
                $recording->getLanguage(),
                array_map(static fn (StoryObject $storyObject): string => (string) $storyObject->getTitle(), $this->storyObjectFinder->linkableStoryObjects($larp)),
            );
            $storyObjectIds = array_map(
                static fn (StoryObject $storyObject): string => $storyObject->getId()->toRfc4122(),
                $this->storyObjectFinder->find($larp, $lines, $summary->mentions),
            );
            $recording->completeSummary($summary->withStoryObjectIds($storyObjectIds));
        } catch (CallSummaryException $failure) {
            $this->logger->warning('Call transcript summary failed', [
                'recording' => $recording->getId()->toRfc4122(),
                'exception' => $failure,
            ]);
            $recording->failSummary($failure->getMessage());
        }

        $recording->getKnowledgeDocument()?->setContent($this->documentRenderer->render($recording));
        $this->entityManager->flush();

        $this->outputDispatcher->afterSummary($recording);
    }
}
