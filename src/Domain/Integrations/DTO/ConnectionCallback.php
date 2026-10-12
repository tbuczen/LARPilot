<?php

namespace App\Domain\Integrations\DTO;

final readonly class ConnectionCallback
{
    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        public string $redirectUri,
        public array $query,
    ) {
    }

    public function authorizationCode(): ?string
    {
        $code = $this->query['code'] ?? null;
        if (!is_string($code) || $code === '') {
            return null;
        }

        return $code;
    }

    public function providerError(): ?string
    {
        $error = $this->query['error'] ?? null;
        if (!is_string($error) || $error === '') {
            return null;
        }

        return $error;
    }
}
