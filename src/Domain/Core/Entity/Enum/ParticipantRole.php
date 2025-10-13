<?php

namespace App\Domain\Core\Entity\Enum;

enum ParticipantRole: string
{
    case ORGANIZER = 'ROLE_ORGANIZER';
    case STAFF = 'ROLE_STAFF';
    case MAIN_STORY_WRITER = 'ROLE_MAIN_STORY_WRITER';
    case STORY_WRITER = 'ROLE_STORY_WRITER';
    case PHOTOGRAPHER = 'ROLE_PHOTOGRAPHER';
    case CRAFTER = 'ROLE_CRAFTER';
    case MAKEUP_ARTIST = 'ROLE_MAKEUP_ARTIST';
    case GAME_MASTER = 'ROLE_GAME_MASTER';
    case NPC_LONG = 'ROLE_NPC_LONG';
    case NPC_SHORT = 'ROLE_NPC_SHORT';
    case PLAYER = 'ROLE_PLAYER';
    case MEDIC = 'ROLE_MEDIC';
    case TRASHER = 'ROLE_TRASHER';
    case TRUST_PERSON = 'ROLE_PERSON_OF_TRUST';
    case OUTFIT_APPROVER = 'ROLE_OUTFIT_APPROVER';
    case ACCOUNTANT = 'ROLE_ACCOUNTANT';
    case GASTRONOMY = 'ROLE_GASTRO';

    public static function getOrganizers(): array
    {
        return [
            self::ORGANIZER,
            self::STAFF,
            self::MAIN_STORY_WRITER,
            self::STORY_WRITER,
            self::PHOTOGRAPHER,
            self::CRAFTER,
            self::MAKEUP_ARTIST,
            self::GAME_MASTER,
            self::NPC_LONG,
            self::NPC_SHORT,
            self::MEDIC,
            self::TRASHER,
            self::TRUST_PERSON,
            self::OUTFIT_APPROVER,
            self::ACCOUNTANT,
            self::GASTRONOMY,
        ];
    }

    public static function getStoryWriters(): array
    {
        return [
            self::MAIN_STORY_WRITER,
            self::STORY_WRITER,
            self::GAME_MASTER,
            self::NPC_LONG,
            self::NPC_SHORT,
        ];
    }
}
