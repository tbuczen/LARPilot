<?php

namespace App\Domain\Integrations\Service\Exceptions;

class SourceSyncForbiddenException extends \RuntimeException
{
    public function __construct(string $message = 'The integration has no access to this source anymore.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
