<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MAIN_STORY_WRITER = 'main_story_writer';
    case STORY_WRITER = 'story_writer';
    case PHOTOGRAPHER = 'photographer';
    case CRAFTER = 'crafter';
    case MAKEUP_ARTIST = 'makeup_artist';
    case GAME_MASTER = 'game_master';
    case NPC_LONG = 'npc_long';
    case NPC_SHORT = 'npc_short';
    case PLAYER = 'player';
    case MEDIC = 'medic';
    case TRASHER = 'trasher';
    case TRUST_PERSON = 'person_of_trust';
    case OUTFIT_APPROVER = 'outfit_approver';
    case ACCOUNTANT = 'accountant';
    case GASTRONOMY = 'gastro';
}