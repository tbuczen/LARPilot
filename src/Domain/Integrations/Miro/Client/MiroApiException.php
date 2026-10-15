<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Client;

use RuntimeException;
use Throwable;

class MiroApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatusCode ?? 0, $previous);
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
