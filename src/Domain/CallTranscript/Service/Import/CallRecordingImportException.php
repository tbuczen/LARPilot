<?php

namespace App\Domain\CallTranscript\Service\Import;

use RuntimeException;

final class CallRecordingImportException extends RuntimeException
{
    public static function unreadableArchive(): self
    {
        return new self('call_recording.import.unreadable_archive');
    }

    public static function tooLarge(): self
    {
        return new self('call_recording.import.too_large');
    }

    public static function noSpeakers(): self
    {
        return new self('call_recording.import.no_speakers');
    }
}
