<?php

namespace App\Service\Integrations\Exceptions;

class ReAuthenticationNeededException extends \Exception
{
    public function __construct(string $integrationId, $code = 0, \Throwable $previous = null)
    {
        parent::__construct('Re-authentication is needed for integration '. $integrationId, $code, $previous);
    }
}