<?php

namespace App\Domain\CallTranscript\Service\SpeechToText;

use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\TransporterException;
use RuntimeException;
use Throwable;

final class SpeechToTextException extends RuntimeException
{
    private bool $retryable = false;

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
        $exception = new self('Speech-to-text provider failed: ' . $previous->getMessage(), 0, $previous);
        $exception->retryable = self::isTransient($previous);

        return $exception;
    }

    public static function temporarilyUnavailable(string $reason): self
    {
        $exception = new self('Speech-to-text provider is temporarily unavailable: ' . $reason);
        $exception->retryable = true;

        return $exception;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    private static function isTransient(Throwable $failure): bool
    {
        if ($failure instanceof RateLimitException || $failure instanceof TransporterException) {
            return true;
        }

        if (!$failure instanceof ErrorException) {
            return false;
        }

        if ($failure->getErrorType() === 'insufficient_quota') {
            return false;
        }

        return $failure->getStatusCode() === 429 || $failure->getStatusCode() >= 500;
    }
}
