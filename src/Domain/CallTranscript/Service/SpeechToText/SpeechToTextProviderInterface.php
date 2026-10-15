<?php

namespace App\Domain\CallTranscript\Service\SpeechToText;

use App\Domain\CallTranscript\DTO\TranscriptSegment;

interface SpeechToTextProviderInterface
{
    /**
     * @return list<TranscriptSegment>
     *
     * @throws SpeechToTextException
     */
    public function transcribe(string $audioPath, ?string $language): array;
}
