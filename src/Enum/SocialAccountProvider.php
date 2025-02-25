<?php

namespace App\Enum;

enum SocialAccountProvider: string
{
    case Facebook = 'facebook';
    case Google = 'google';
    case Discord = 'discord';
}
