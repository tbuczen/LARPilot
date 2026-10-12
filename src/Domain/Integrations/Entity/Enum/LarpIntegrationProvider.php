<?php

namespace App\Domain\Integrations\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum LarpIntegrationProvider: string implements LabelableEnumInterface
{
    case Facebook = 'facebook';
    case Google = 'integration_google_drive';
    case Discord = 'discord';
    case Miro = 'miro';


    public function getLabel(): string
    {
        return match ($this) {
            self::Google => 'Google Drive',
            self::Facebook => 'Facebook',
            self::Discord => 'Discord',
            self::Miro => 'Miro',
        };
    }

    public function descriptionKey(): string
    {
        return match ($this) {
            self::Google => 'larp.integration.googleDriveDescription',
            self::Facebook => 'larp.integration.facebookDescription',
            self::Discord => 'larp.integration.discordDescription',
            self::Miro => 'larp.integration.miroDescription',
        };
    }

    public function integrationSettingsTemplate(): ?string
    {
        return match ($this) {
            self::Google => 'partials/_googleDriveIntegration.html.twig',
            default => null,
        };
    }

    public function sameAs(self $other): bool
    {
        return $this === $other;
    }
}
