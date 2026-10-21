<?php

namespace App\Domain\CallTranscript\Service\Audio;

use App\Domain\CallTranscript\DTO\AudioChunk;

interface AudioChunkerInterface
{
    /**
     * @return list<AudioChunk>
     *
     * @throws AudioChunkingException
     */
    public function split(string $audioPath, string $workDirectory, int $maxBytes): array;
}
