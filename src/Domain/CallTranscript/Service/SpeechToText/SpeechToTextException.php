<?php

namespace App\Domain\CallTranscript\Service\SpeechToText;

use RuntimeException;
use Throwable;

final class SpeechToTextException extends RuntimeException
{
    public static function fileTooLarge(int $bytes, int $limit): self
    {
        return new self(sprintf('Audio track is %d MB, the provider accepts at most %d MB.', intdiv($bytes, 1024 * 1024), intdiv($limit, 1024 * 1024)));
    }

    public static function unreadableFile(): self
    {
        return new self('Audio track could not be read.');
    }

    public static function providerFailed(Throwable $previous): self
    {
        return new self('Speech-to-text provider failed: ' . $previous->getMessage(), 0, $previous);
    }
}
