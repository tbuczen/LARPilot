<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Client;

use Throwable;

class MiroRateLimitExceededException extends MiroApiException
{
    public function __construct(
        string $message,
        private readonly int $retryAfterSeconds = 1,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $previous);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
