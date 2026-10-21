<?php

namespace App\Domain\CallTranscript\Service\Summary;

use RuntimeException;
use Throwable;

final class CallSummaryException extends RuntimeException
{
    public static function emptyTranscript(): self
    {
        return new self('The transcript is empty, there is nothing to summarize.');
    }

    public static function invalidResponse(): self
    {
        return new self('The AI model did not return a valid summary.');
    }

    public static function providerFailed(Throwable $previous): self
    {
        return new self('The AI model failed: ' . $previous->getMessage(), 0, $previous);
    }
}
