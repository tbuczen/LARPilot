<?php

namespace App\Entity\Enum;

enum LarpIntegrationProvider: string
{
    case Facebook = 'facebook';
    case Google = 'integration_google_drive';
    case Discord = 'discord';
    case Asana = 'asana';
    case Trello = 'trello';
    case Miro = 'miro';


    public function displayName(): string
    {
        return match ($this) {
            self::Google => 'Google Drive',
            self::Trello => 'Trello',
            self::Miro => 'Miro',
            self::Asana => 'Asana',
            self::Facebook => 'Facebook',
            self::Discord => 'Discord',
        };
    }

    public function descriptionKey(): ?string
    {
        return match ($this) {
            self::Google => 'backoffice.larp.integration.googleDriveDescription',
            self::Trello => 'backoffice.larp.integration.trelloDescription',
            self::Miro => 'backoffice.larp.integration.miroDescription',
            self::Asana => 'backoffice.larp.integration.asanaDescription',
            self::Facebook => 'backoffice.larp.integration.facebookDescription',
            self::Discord => 'backoffice.larp.integration.discordDescription',
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
