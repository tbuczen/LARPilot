<?php

namespace App\Domain\Integrations\Enum;

enum IntegrationFileType: string
{
    case DOCUMENT = 'document';
    case SPREADSHEET = 'spreadsheet';
    case FOLDER = 'folder';

    public static function fromMimeType(string $mimeType): self
    {
        return match ($mimeType) {
            'application/vnd.google-apps.folder' => self::FOLDER,
            'application/vnd.google-apps.spreadsheet' => self::SPREADSHEET,
            'application/vnd.google-apps.document' => self::DOCUMENT,
            default => self::DOCUMENT, // Fallback: Treat unknown types as documents
        };
    }
}
