<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\DTO\SpreadsheetPreview;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use Psr\Log\LoggerInterface;

class SpreadsheetMappingPreviewProvider
{
    public const PREVIEW_ROW_LIMIT = 5;
    private const SPREADSHEET_MIME_TYPE = 'application/vnd.google-apps.spreadsheet';

    /** @var array<string, SpreadsheetPreview> */
    private array $previews = [];

    public function __construct(
        private readonly IntegrationRegistry $integrationRegistry,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $metaValues
     */
    public function previewFor(SharedFile $sharedFile, array $metaValues): ?SpreadsheetPreview
    {
        if (!str_starts_with($sharedFile->getMimeType(), self::SPREADSHEET_MIME_TYPE)) {
            return null;
        }

        $sheetName = is_string($metaValues['sheetName'] ?? null) && $metaValues['sheetName'] !== '' ? $metaValues['sheetName'] : null;
        $startingRow = is_numeric($metaValues['startingRow'] ?? null) ? (int) $metaValues['startingRow'] : 2;
        $headerRowNumber = max(1, $startingRow - 1);

        $previewKey = implode('|', [$sharedFile->getId()->toRfc4122(), $sheetName ?? '', $headerRowNumber]);

        return $this->previews[$previewKey] ??= $this->fetchPreview($sharedFile, $sheetName, $headerRowNumber);
    }

    /**
     * @param array<string, mixed> $metaValues
     * @return array{mimeType: string, spreadsheet_preview: ?SpreadsheetPreview}
     */
    public function formOptionsFor(SharedFile $sharedFile, array $metaValues): array
    {
        return [
            'mimeType' => $sharedFile->getMimeType(),
            'spreadsheet_preview' => $this->previewFor($sharedFile, $metaValues),
        ];
    }

    private function fetchPreview(SharedFile $sharedFile, ?string $sheetName, int $headerRowNumber): SpreadsheetPreview
    {
        try {
            $fileSource = $this->integrationRegistry->capability($sharedFile->getIntegration(), FileSourceIntegrationInterface::class);

            return $fileSource->fetchSpreadsheetPreview($sharedFile, $sheetName, $headerRowNumber, self::PREVIEW_ROW_LIMIT);
        } catch (\Throwable $exception) {
            $this->logger->warning('Spreadsheet preview failed', [
                'sharedFile' => $sharedFile->getId()->toRfc4122(),
                'exception' => $exception,
            ]);

            return SpreadsheetPreview::unavailable(mb_strimwidth($exception->getMessage(), 0, 200, '…'));
        }
    }
}
