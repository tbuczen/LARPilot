<?php

namespace App\Domain\Integrations\DTO;

final readonly class RefreshedToken
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?\DateTimeImmutable $expiresAt,
    ) {
    }

    public static function fromExpiresIn(string $accessToken, ?string $refreshToken, int|string|null $expiresIn): self
    {
        if ($expiresIn === null || $expiresIn === '') {
            return new self($accessToken, $refreshToken, null);
        }

        return new self($accessToken, $refreshToken, new \DateTimeImmutable(sprintf('+%d seconds', (int) $expiresIn)));
    }
}
