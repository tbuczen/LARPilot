<?php

namespace App\Domain\CallTranscript\MessageHandler;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Message\ExportCallTranscriptToGoogleDocMessage;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Service\CallTranscriptOutputDispatcher;
use App\Domain\CallTranscript\Service\GoogleDoc\CallTranscriptGoogleDocExporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class ExportCallTranscriptToGoogleDocHandler
{
    public function __construct(
        private CallRecordingRepository $recordingRepository,
        private CallTranscriptGoogleDocExporter $exporter,
        private CallTranscriptOutputDispatcher $outputDispatcher,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExportCallTranscriptToGoogleDocMessage $message): void
    {
        $recording = $this->recordingRepository->find($message->recordingId);
        if (!$recording instanceof CallRecording || !$recording->isCompleted() || $recording->getGoogleDocStatus() !== CallOutputStatus::PENDING) {
            return;
        }

        try {
            $document = $this->exporter->export($recording);
            $recording->completeGoogleDoc($document['id'], $document['url']);
        } catch (Throwable $failure) {
            $this->logger->warning('Call transcript Google Doc export failed', [
                'recording' => $recording->getId()->toRfc4122(),
                'exception' => $failure,
            ]);
            $recording->failGoogleDoc(self::reason($failure));
        }

        $this->entityManager->flush();
        $this->outputDispatcher->afterGoogleDoc($recording);
    }

    private static function reason(Throwable $failure): string
    {
        $message = trim($failure->getMessage());
        $decoded = json_decode($message, true);
        if (is_array($decoded) && is_string($decoded['error']['message'] ?? null)) {
            $message = $decoded['error']['message'];
        }

        return mb_substr($message === '' ? $failure::class : $message, 0, 500);
    }
}
