<?php

namespace App\Domain\Integrations\Service\Exceptions;

class SourceSyncRateLimitedException extends \RuntimeException
{
    public function __construct(
        private readonly int $retryAfterSeconds,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Provider rate limit reached, retry after %d seconds.', $retryAfterSeconds), 429, $previous);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
