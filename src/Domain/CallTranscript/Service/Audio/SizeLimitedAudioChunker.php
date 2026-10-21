<?php

namespace App\Domain\CallTranscript\Service\Audio;

use App\Domain\CallTranscript\DTO\AudioChunk;

final readonly class SizeLimitedAudioChunker implements AudioChunkerInterface
{
    public function __construct(
        private FfmpegLocator $ffmpegLocator,
        private FfmpegAudioChunker $ffmpegChunker,
        private Mp3FrameAudioChunker $mp3Chunker,
    ) {
    }

    public function split(string $audioPath, string $workDirectory, int $maxBytes): array
    {
        $bytes = @filesize($audioPath);
        if ($bytes === false) {
            throw AudioChunkingException::unreadable($audioPath);
        }

        if ($bytes <= $maxBytes) {
            return [new AudioChunk($audioPath, 0.0, false)];
        }

        if ($this->ffmpegLocator->isAvailable()) {
            return $this->ffmpegChunker->split($audioPath, $workDirectory, $maxBytes);
        }

        if (Mp3FrameAudioChunker::supports($audioPath)) {
            return $this->mp3Chunker->split($audioPath, $workDirectory, $maxBytes);
        }

        return [new AudioChunk($audioPath, 0.0, false)];
    }
}
