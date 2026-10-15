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
        $insideNotes = false;
        foreach (preg_split('/\R/', $info) ?: [] as $line) {
            if (preg_match('/^\s*Notes:\s*$/i', $line) === 1) {
                $insideNotes = true;
                continue;
            }

            if (!$insideNotes) {
                continue;
            }

            if (preg_match('/^\S.*:\s*$/', $line) === 1) {
                $insideNotes = false;
                continue;
            }

            if (preg_match(self::NOTE_PATTERN, $line, $matches) !== 1) {
                continue;
            }

            $notes[] = new TranscriptSegment(TranscriptTimestamp::toSeconds($matches[1]), $matches[2]);
        }

        return $notes;
    }
}
