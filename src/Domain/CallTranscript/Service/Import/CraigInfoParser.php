<?php

namespace App\Domain\CallTranscript\Service\Import;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Service\Transcript\TranscriptTimestamp;
use DateTimeImmutable;
use Exception;

final class CraigInfoParser
{
    private const START_TIME_PATTERN = '/^\s*Start time:\s*(.+?)\s*$/mi';
    private const NOTE_PATTERN = '/^\s*(\d+:\d{1,2}:\d{2}(?:[.,]\d+)?)\s*:\s*(.+?)\s*$/';
    private const TRACK_PATTERN = '/^\s+(.+?)\s*\((\d{1,20})\)\s*$/';

    public function parseStartTime(string $info): ?DateTimeImmutable
    {
        if (preg_match(self::START_TIME_PATTERN, $info, $matches) !== 1) {
            return null;
        }

        try {
            return new DateTimeImmutable($matches[1]);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @return list<TranscriptSegment>
     */
    public function parseNotes(string $info): array
    {
        $notes = [];
        foreach ($this->sectionLines($info, 'Notes') as $line) {
            if (preg_match(self::NOTE_PATTERN, $line, $matches) !== 1) {
                continue;
            }

            $notes[] = new TranscriptSegment(TranscriptTimestamp::toSeconds($matches[1]), $matches[2]);
        }

        return $notes;
    }

    /**
     * @return array<string, string>
     */
    public function parseTrackUserIds(string $info): array
    {
        $userIds = [];
        foreach ($this->sectionLines($info, 'Tracks') as $line) {
            if (preg_match(self::TRACK_PATTERN, $line, $matches) !== 1) {
                continue;
            }

            $userIds[self::speakerKey($matches[1])] = $matches[2];
        }

        return $userIds;
    }

    public static function speakerKey(string $speaker): string
    {
        return mb_strtolower(trim((string) preg_replace('/(?:#\d{1,4}|_\d{1,4})$/', '', trim($speaker))));
    }

    /**
     * @return list<string>
     */
    private function sectionLines(string $info, string $section): array
    {
        $lines = [];
        $insideSection = false;
        foreach (preg_split('/\R/', $info) ?: [] as $line) {
            if (preg_match('/^\s*' . preg_quote($section, '/') . ':\s*$/i', $line) === 1) {
                $insideSection = true;
                continue;
            }

            if (!$insideSection) {
                continue;
            }

            if (preg_match('/^\S.*:\s*$/', $line) === 1 || preg_match('/^\S/', $line) === 1) {
                $insideSection = false;
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }
}
