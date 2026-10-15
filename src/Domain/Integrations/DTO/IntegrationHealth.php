<?php

namespace App\Domain\Integrations\DTO;

use App\Domain\Integrations\Entity\Enum\IntegrationStatus;

final readonly class IntegrationHealth
{
    public function __construct(
        public IntegrationStatus $status,
        public ?string $message = null,
    ) {
    }

    public static function healthy(): self
    {
        return new self(IntegrationStatus::Active);
    }

    public static function needsReauth(?string $message): self
    {
        return new self(IntegrationStatus::NeedsReauth, $message);
    }

    public function isHealthy(): bool
    {
        return $this->status->isUsable();
    }
}
