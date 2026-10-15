<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\SharedFile;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SharedFile>
 */
final class SharedFileFactory extends PersistentProxyObjectFactory
{
    public const SPREADSHEET_MIME_TYPE = 'application/vnd.google-apps.spreadsheet';
    public const DOCUMENT_MIME_TYPE = 'application/vnd.google-apps.document';
    public const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    public static function class(): string
    {
        return SharedFile::class;
    }

    protected function defaults(): array
    {
        $fileId = self::faker()->regexify('[A-Za-z0-9_-]{44}');

        return [
            'fileId' => $fileId,
            'fileName' => self::faker()->words(3, true),
            'integration' => LarpIntegrationFactory::new(),
            'metadata' => [],
            'permissionType' => 'writer',
            'mimeType' => self::SPREADSHEET_MIME_TYPE,
            'url' => 'https://docs.google.com/spreadsheets/d/' . $fileId . '/edit',
        ];
    }

    public function forIntegration(mixed $integration): self
    {
        return $this->with([
            'integration' => $integration,
        ]);
    }

    public function spreadsheet(): self
    {
        return $this->with(function (): array {
            $fileId = self::faker()->regexify('[A-Za-z0-9_-]{44}');

            return [
                'fileId' => $fileId,
                'mimeType' => self::SPREADSHEET_MIME_TYPE,
                'url' => 'https://docs.google.com/spreadsheets/d/' . $fileId . '/edit',
            ];
        });
    }

    public function document(): self
    {
        return $this->with(function (): array {
            $fileId = self::faker()->regexify('[A-Za-z0-9_-]{44}');

            return [
                'fileId' => $fileId,
                'mimeType' => self::DOCUMENT_MIME_TYPE,
                'url' => 'https://docs.google.com/document/d/' . $fileId . '/edit',
            ];
        });
    }

    public function folder(): self
    {
        return $this->with(function (): array {
            $fileId = self::faker()->regexify('[A-Za-z0-9_-]{33}');

            return [
                'fileId' => $fileId,
                'mimeType' => self::FOLDER_MIME_TYPE,
                'url' => 'https://drive.google.com/drive/folders/' . $fileId,
            ];
        });
    }

    public function readOnly(): self
    {
        return $this->with([
            'permissionType' => 'reader',
        ]);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return $this->with([
            'metadata' => $metadata,
        ]);
    }
}
