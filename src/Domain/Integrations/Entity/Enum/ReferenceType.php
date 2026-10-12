<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ReferenceType: string
{
    case SpreadsheetRow = 'spreadsheet_row';
    case Spreadsheet = 'spreadsheet';
    case Document = 'document';
    case DocumentParagraph = 'document_paragraph';
    case Thread = 'thread';
    case Comment = 'comment';
    case Board = 'board';
    case BoardItem = 'board_item';
    case BoardFrame = 'board_frame';
    case BoardConnector = 'board_connector';
    case ForumPost = 'forum_post';
    case Url = 'url'; // fallback / generic
}
