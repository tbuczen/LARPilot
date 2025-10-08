<?php

namespace App\Entity\Enum;

enum CharacterType: string
{
    case Player = 'player';
    case LongNpc = 'long_npc';
    case ShortNpc = 'short_npc';
    case GameMaster = 'gm';
    case GenericNpc = 'generic_npc'; //np raider/bandit/monk - ktoś kto nie ma imienia i gra jako tło w grupie
}
