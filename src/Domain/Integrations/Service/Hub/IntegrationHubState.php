<?php

namespace App\Domain\Integrations\Service\Hub;

enum IntegrationHubState: string
{
    case Connected = 'connected';
    case NeedsReconnect = 'needs_reconnect';
    case Error = 'error';
    case NotConnected = 'not_connected';

    public function labelKey(): string
    {
        return 'integration_hub.state.' . $this->value;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Connected => 'bg-success',
            self::NeedsReconnect => 'bg-warning text-dark',
            self::Error => 'bg-danger',
            self::NotConnected => 'bg-secondary',
        };
    }

    public function needsAttention(): bool
    {
        return $this === self::NeedsReconnect || $this === self::Error;
    }

    public function isConnected(): bool
    {
        return $this !== self::NotConnected;
    }
}
