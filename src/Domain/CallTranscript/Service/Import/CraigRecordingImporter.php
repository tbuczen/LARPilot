<?php

namespace App\Domain\CallTranscript\Service\Import;

use App\Domain\CallTranscript\DTO\ImportedCallRecording;
use App\Domain\CallTranscript\DTO\ImportedCallTrack;
use ZipArchive;

final readonly class CraigRecordingImporter implements CallRecordingImporterInterface
{
    public const AUDIO_EXTENSIONS = ['flac', 'ogg', 'oga', 'opus', 'mp3', 'm4a', 'aac', 'wav', 'webm'];
    public const TRANSCRIPT_EXTENSIONS = ['vtt', 'srt', 'txt'];
    private const INFO_FILENAME = 'info.txt';
    private const MAX_UNCOMPRESSED_BYTES = 4 * 1024 * 1024 * 1024;

    public function __construct(
        private CraigInfoParser $infoParser,
    ) {
    }

    public function import(string $archivePath, string $targetDirectory): ImportedCallRecording
    {
        $archive = new ZipArchive();
        if ($archive->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw CallRecordingImportException::unreadableArchive();
        }

        try {
            return $this->importEntries($archive, $targetDirectory);
        } finally {
            $archive->close();
        }
    }

    private function importEntries(ZipArchive $archive, string $targetDirectory): ImportedCallRecording
    {
        $info = null;
        $audioBySpeaker = [];
        $transcriptsBySpeaker = [];
        $uncompressedBytes = 0;

        for ($index = 0; $index < $archive->numFiles; ++$index) {
            $entry = $archive->statIndex($index);
            if ($entry === false) {
                continue;
            }

            $entryName = str_replace('\\', '/', $entry['name']);
            $filename = basename($entryName);
            if (str_ends_with($entryName, '/') || $filename === '' || str_starts_with($filename, '.') || str_contains($entryName, '__MACOSX/')) {
                continue;
            }

            $uncompressedBytes += $entry['size'];
            if ($uncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw CallRecordingImportException::tooLarge();
            }

            if (strtolower($filename) === self::INFO_FILENAME) {
                $info = (string) $archive->getFromIndex($index);
                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $speaker = self::speakerFromFilename($filename);
            if ($speaker === '') {
                continue;
            }

            if (in_array($extension, self::AUDIO_EXTENSIONS, true)) {
                $audioBySpeaker[self::uniqueSpeaker($speaker, $audioBySpeaker)] = $this->extract($archive, $index, $targetDirectory, $extension);
                continue;
            }

            if (!in_array($extension, self::TRANSCRIPT_EXTENSIONS, true)) {
                continue;
            }

            $current = $transcriptsBySpeaker[$speaker] ?? null;
            if ($current !== null && self::transcriptPriority($current['extension']) <= self::transcriptPriority($extension)) {
                continue;
            }

            $transcriptsBySpeaker[$speaker] = ['index' => $index, 'extension' => $extension];
        }

        $userIds = $info === null ? [] : $this->infoParser->parseTrackUserIds($info);
        $tracks = [];
        foreach ($audioBySpeaker as $speaker => $audioFilename) {
            $transcript = $transcriptsBySpeaker[$speaker] ?? null;
            unset($transcriptsBySpeaker[$speaker]);
            $tracks[] = new ImportedCallTrack(
                (string) $speaker,
                $audioFilename,
                $transcript === null ? null : $this->extract($archive, $transcript['index'], $targetDirectory, $transcript['extension']),
                $userIds[CraigInfoParser::speakerKey((string) $speaker)] ?? null,
            );
        }

        foreach ($transcriptsBySpeaker as $speaker => $transcript) {
            $tracks[] = new ImportedCallTrack(
                (string) $speaker,
                null,
                $this->extract($archive, $transcript['index'], $targetDirectory, $transcript['extension']),
                $userIds[CraigInfoParser::speakerKey((string) $speaker)] ?? null,
            );
        }

        if ($tracks === []) {
            throw CallRecordingImportException::noSpeakers();
        }

        usort($tracks, static fn (ImportedCallTrack $left, ImportedCallTrack $right): int => strcasecmp($left->speaker, $right->speaker));

        return new ImportedCallRecording(
            $tracks,
            $info === null ? null : $this->infoParser->parseStartTime($info),
            $info === null ? [] : $this->infoParser->parseNotes($info),
        );
    }

    public static function speakerFromFilename(string $filename): string
    {
        $stem = pathinfo($filename, PATHINFO_FILENAME);

        return trim((string) preg_replace('/^\d+-/', '', $stem));
    }

    /**
     * @param array<string, string> $audioBySpeaker
     */
    private static function uniqueSpeaker(string $speaker, array $audioBySpeaker): string
    {
        $candidate = $speaker;
        $suffix = 2;
        while (array_key_exists($candidate, $audioBySpeaker)) {
            $candidate = sprintf('%s (%d)', $speaker, $suffix++);
        }

        return $candidate;
    }

    private static function transcriptPriority(string $extension): int
    {
        return (int) array_search($extension, self::TRANSCRIPT_EXTENSIONS, true);
    }

    private function extract(ZipArchive $archive, int $index, string $targetDirectory, string $extension): string
    {
        $source = $archive->getStream((string) $archive->getNameIndex($index));
        if ($source === false) {
            throw CallRecordingImportException::unreadableArchive();
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $target = fopen($targetDirectory . '/' . $filename, 'wb');
        if ($target === false) {
            fclose($source);
            throw CallRecordingImportException::unreadableArchive();
        }

        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        return $filename;
    }
}
