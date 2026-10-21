<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutput;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Message\ExportCallTranscriptToGoogleDocMessage;
use App\Domain\CallTranscript\Message\GenerateCallSummaryMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CallTranscriptOutputDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function afterTranscript(CallRecording $recording): void
    {
        if ($recording->getSummaryStatus() === CallOutputStatus::PENDING) {
            $this->messageBus->dispatch(new GenerateCallSummaryMessage($recording->getId()));

            return;
        }

        $this->afterSummary($recording);
    }

    public function afterSummary(CallRecording $recording): void
    {
        if ($recording->getGoogleDocStatus() === CallOutputStatus::PENDING) {
            $this->messageBus->dispatch(new ExportCallTranscriptToGoogleDocMessage($recording->getId()));

            return;
        }

        $this->afterGoogleDoc($recording);
    }

    public function afterGoogleDoc(CallRecording $recording): void
    {
    }

    public function retry(CallRecording $recording, CallOutput $output): bool
    {
        if (!$recording->isCompleted() || !$recording->outputStatus($output)->isRetryable()) {
            return false;
        }

        match ($output) {
            CallOutput::SUMMARY => $recording->requestSummary(true),
            CallOutput::GOOGLE_DOC => $recording->retryGoogleDoc(),
        };
        $this->entityManager->flush();

        $this->messageBus->dispatch(match ($output) {
            CallOutput::SUMMARY => new GenerateCallSummaryMessage($recording->getId()),
            CallOutput::GOOGLE_DOC => new ExportCallTranscriptToGoogleDocMessage($recording->getId()),
        });

        return true;
    }
}
