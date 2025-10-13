<?php

namespace App\Domain\Integrations\Entity\Enum;

enum SocialAccountProvider: string
{
    case Facebook = 'facebook';
    case Google = 'google';
    case Discord = 'discord';
}
