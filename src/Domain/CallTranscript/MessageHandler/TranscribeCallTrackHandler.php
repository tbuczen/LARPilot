<?php

namespace App\Domain\CallTranscript\MessageHandler;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Message\FinalizeCallTranscriptMessage;
use App\Domain\CallTranscript\Message\TranscribeCallTrackMessage;
use App\Domain\CallTranscript\Repository\CallRecordingTrackRepository;
use App\Domain\CallTranscript\Service\CallRecordingStorage;
use App\Domain\CallTranscript\Service\SpeechToText\CallTrackAudioTranscriber;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextException;
use App\Domain\CallTranscript\Service\Transcript\SubtitleParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class TranscribeCallTrackHandler
{
    public const MAX_ATTEMPTS = 4;
    public const RETRY_DELAY_MILLISECONDS = 60000;

    public function __construct(
        private CallRecordingTrackRepository $trackRepository,
        private CallRecordingStorage $storage,
        private CallTrackAudioTranscriber $audioTranscriber,
        private SubtitleParser $subtitleParser,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TranscribeCallTrackMessage $message): void
    {
        $track = $this->trackRepository->find($message->trackId);
        if (!$track instanceof CallRecordingTrack || !$track->isPending()) {
            return;
        }

        $recording = $track->getRecording();
        $audioFilename = $track->getAudioFilename();
        $transcriptFilename = $track->getTranscriptFilename();

        $track->registerAttempt();

        try {
            $track->markTranscribed($this->segmentsFor($track));
        } catch (SpeechToTextException $failure) {
            if ($failure->isRetryable() && $track->getAttempts() < self::MAX_ATTEMPTS) {
                $this->trackRepository->flush();
                $this->logger->notice('Call track transcription will be retried', [
                    'track' => $track->getId()->toRfc4122(),
                    'attempt' => $track->getAttempts(),
                    'reason' => $failure->getMessage(),
                ]);

                throw new RecoverableMessageHandlingException($failure->getMessage(), 0, $failure, self::RETRY_DELAY_MILLISECONDS * 2 ** ($track->getAttempts() - 1));
            }

            $this->logger->warning('Call track transcription failed', [
                'track' => $track->getId()->toRfc4122(),
                'reason' => $failure->getMessage(),
            ]);
            $track->markFailed($failure->getMessage());
        }

        $this->storage->removeFile($recording, $audioFilename);
        $this->storage->removeFile($recording, $transcriptFilename);
        $this->trackRepository->flush();

        if ($this->trackRepository->countPending($recording) === 0) {
            $this->messageBus->dispatch(new FinalizeCallTranscriptMessage($recording->getId()));
        }
    }

    /**
     * @return list<TranscriptSegment>
     */
    private function segmentsFor(CallRecordingTrack $track): array
    {
        $recording = $track->getRecording();
        $transcriptFilename = $track->getTranscriptFilename();
        if ($transcriptFilename !== null) {
            $content = @file_get_contents($this->storage->pathFor($recording, $transcriptFilename));
            if ($content === false) {
                throw SpeechToTextException::unreadableFile();
            }

            return $this->subtitleParser->parse($content, pathinfo($transcriptFilename, PATHINFO_EXTENSION));
        }

        $audioFilename = $track->getAudioFilename();
        if ($audioFilename === null) {
            return [];
        }

        return $this->audioTranscriber->transcribe(
            $this->storage->pathFor($recording, $audioFilename),
            $recording->getLanguage(),
            $this->storage->pathFor($recording, 'work-' . $track->getId()->toRfc4122()),
        );
    }
}
