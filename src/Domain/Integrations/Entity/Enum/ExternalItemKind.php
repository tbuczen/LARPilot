<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalItemKind: string
{
    case Thread = 'thread';
    case ForumPost = 'forum_post';
    case Message = 'message';
    case BoardItem = 'board_item';
    case Frame = 'frame';
    case Connector = 'connector';

    public function referenceType(): ReferenceType
    {
        return match ($this) {
            self::Thread => ReferenceType::Thread,
            self::ForumPost => ReferenceType::ForumPost,
            self::Message => ReferenceType::Comment,
            self::BoardItem => ReferenceType::BoardItem,
            self::Frame => ReferenceType::BoardFrame,
            self::Connector => ReferenceType::BoardConnector,
        };
    }
}
