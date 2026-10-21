<?php

namespace App\Domain\CallTranscript\MessageHandler;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Message\PostCallTranscriptToDiscordMessage;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Service\Discord\CallTranscriptDiscordPoster;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class PostCallTranscriptToDiscordHandler
{
    public function __construct(
        private CallRecordingRepository $recordingRepository,
        private CallTranscriptDiscordPoster $poster,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PostCallTranscriptToDiscordMessage $message): void
    {
        $recording = $this->recordingRepository->find($message->recordingId);
        if (!$recording instanceof CallRecording || !$recording->isCompleted() || $recording->getDiscordStatus() !== CallOutputStatus::PENDING) {
            return;
        }

        try {
            $this->poster->post($recording);
            $recording->completeDiscordPost();
        } catch (Throwable $failure) {
            $this->logger->warning('Posting a call transcript to Discord failed', [
                'recording' => $recording->getId()->toRfc4122(),
                'exception' => $failure,
            ]);
            $recording->failDiscordPost(mb_substr($failure->getMessage(), 0, 500));
        }

        $this->entityManager->flush();
    }
}
