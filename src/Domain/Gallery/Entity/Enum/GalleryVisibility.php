<?php

namespace App\Domain\Gallery\Entity\Enum;

enum GalleryVisibility: string
{
    case PUBLIC = 'public';
    case PARTICIPANTS_ONLY = 'participants_only';
    case ORGANIZERS_ONLY = 'organizers_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => 'gallery.visibility.public',
            self::PARTICIPANTS_ONLY => 'gallery.visibility.participants_only',
            self::ORGANIZERS_ONLY => 'gallery.visibility.organizers_only',
        };
    }
}
