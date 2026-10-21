<?php

namespace App\Domain\CallTranscript\Service\GoogleDoc;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Service\CallTranscriptDocumentRenderer;
use App\Domain\Core\Service\Helper\DateFormat;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Service\Google\GoogleClientFactoryInterface;
use DateTimeZone;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CallTranscriptGoogleDocExporter
{
    public const GOOGLE_DOC_MIME_TYPE = 'application/vnd.google-apps.document';
    private const HTML_MIME_TYPE = 'text/html';

    public function __construct(
        private GoogleClientFactoryInterface $googleClientFactory,
        private CallTranscriptDocumentRenderer $documentRenderer,
    ) {
    }

    /**
     * @return array{id: string, url: string}
     */
    public function export(CallRecording $recording): array
    {
        $folder = $recording->getGoogleDocFolder();
        if (!$folder instanceof SharedFile) {
            throw new RuntimeException('The Google Drive folder for call transcripts is no longer available.');
        }

        $drive = new Drive($this->googleClientFactory->getClientForIntegration($folder->getIntegration()));
        $parameters = [
            'data' => $this->html($recording),
            'mimeType' => self::HTML_MIME_TYPE,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink',
        ];

        $existingDocumentId = $recording->getGoogleDocId();
        $document = $existingDocumentId === null
            ? $drive->files->create(new DriveFile([
                'name' => self::documentName($recording),
                'mimeType' => self::GOOGLE_DOC_MIME_TYPE,
                'parents' => [$folder->getFileId()],
            ]), $parameters)
            : $drive->files->update($existingDocumentId, new DriveFile(['name' => self::documentName($recording)]), $parameters);

        $documentId = (string) $document->getId();
        if ($documentId === '') {
            throw new RuntimeException('Google Drive did not return the created document.');
        }

        return [
            'id' => $documentId,
            'url' => $document->getWebViewLink() ?: sprintf('https://docs.google.com/document/d/%s/edit', $documentId),
        ];
    }

    public static function documentName(CallRecording $recording): string
    {
        $date = ($recording->getRecordingStartedAt() ?? $recording->getCreatedAt())
            ->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return sprintf('%s – %s', $recording->getTitle(), $date->format(DateFormat::DATE));
    }

    private function html(CallRecording $recording): string
    {
        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>%1$s</title></head><body><h1>%1$s</h1>%2$s</body></html>',
            htmlspecialchars($recording->getTitle(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $this->documentRenderer->render($recording, UrlGeneratorInterface::ABSOLUTE_URL),
        );
    }
}
