<?php

namespace App\Domain\Integrations\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum LarpIntegrationProvider: string implements LabelableEnumInterface
{
    case Facebook = 'facebook';
    case Google = 'integration_google_drive';
    //    case Discord = 'discord';
    //    case Asana = 'asana';
    //    case Trello = 'trello';
    //    case Miro = 'miro';


    public function getLabel(): string
    {
        return match ($this) {
            self::Google => 'Google Drive',
            self::Facebook => 'Facebook',
        };
    }

    public function descriptionKey(): string
    {
        return match ($this) {
            self::Google => 'larp.integration.googleDriveDescription',
            self::Facebook => 'larp.integration.facebookDescription',
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
