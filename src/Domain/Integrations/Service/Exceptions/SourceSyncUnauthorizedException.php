<?php

namespace App\Domain\Integrations\Service\Exceptions;

class SourceSyncUnauthorizedException extends \RuntimeException
{
    public function __construct(string $message = 'The provider rejected the integration credentials.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
