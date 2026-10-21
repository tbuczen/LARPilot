<?php

namespace App\Domain\CallTranscript\Service\SpeechToText;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Service\Audio\AudioChunkerInterface;
use App\Domain\CallTranscript\Service\Audio\AudioChunkingException;
use Symfony\Component\Filesystem\Filesystem;

final readonly class CallTrackAudioTranscriber
{
    public const MAX_CHUNK_BYTES = 24 * 1024 * 1024;

    public function __construct(
        private AudioChunkerInterface $audioChunker,
        private SpeechToTextProviderInterface $speechToTextProvider,
        private int $maxChunkBytes = self::MAX_CHUNK_BYTES,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @return list<TranscriptSegment>
     *
     * @throws SpeechToTextException
     */
    public function transcribe(string $audioPath, ?string $language, string $workDirectory): array
    {
        $this->filesystem->mkdir($workDirectory, 0700);

        try {
            try {
                $chunks = $this->audioChunker->split($audioPath, $workDirectory, $this->maxChunkBytes);
            } catch (AudioChunkingException $failure) {
                throw new SpeechToTextException($failure->getMessage(), 0, $failure);
            }

            $segments = [];
            foreach ($chunks as $chunk) {
                foreach ($this->speechToTextProvider->transcribe($chunk->path, $language) as $segment) {
                    $segments[] = new TranscriptSegment($segment->startSeconds + $chunk->offsetSeconds, $segment->text);
                }
            }

            return $segments;
        } finally {
            $this->filesystem->remove($workDirectory);
        }
    }
}
