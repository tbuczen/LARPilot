<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutput;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
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
    }

    public function retry(CallRecording $recording, CallOutput $output): bool
    {
        if (!$recording->isCompleted() || !$recording->outputStatus($output)->isRetryable()) {
            return false;
        }

        match ($output) {
            CallOutput::SUMMARY => $recording->requestSummary(true),
        };
        $this->entityManager->flush();

        match ($output) {
            CallOutput::SUMMARY => $this->messageBus->dispatch(new GenerateCallSummaryMessage($recording->getId())),
        };

        return true;
    }
}
