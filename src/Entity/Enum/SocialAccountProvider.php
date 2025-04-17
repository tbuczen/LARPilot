<?php

namespace App\Entity\Enum;

enum SocialAccountProvider: string
{
    case Facebook = 'facebook';
    case Google = 'google';
    case Discord = 'discord';
}
