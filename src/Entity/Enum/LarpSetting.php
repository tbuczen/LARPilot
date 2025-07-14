<?php

namespace App\Entity\Enum;

enum LarpSetting: string
{
    case FANTASY = 'fantasy';
    case SCI_FI = 'sci-fi';
    case CYBERPUNK = 'cyberpunk';
    case HISTORIC = 'history';
    case BATTLE = 'battle';
    case NOIR = 'noir';
    case POST_APO = 'postapo';

    public function getLabel(): string
    {
        return match ($this) {
            self::FANTASY => 'Fantasy',
            self::SCI_FI => 'Science Fiction',
            self::CYBERPUNK => 'Cyberpunk',
            self::HISTORIC => 'Historic / Reconstruction',
            self::BATTLE => 'Battle',
            self::NOIR => 'Noir',
            self::POST_APO => 'Postapocalyptic',
        };
    }
}
