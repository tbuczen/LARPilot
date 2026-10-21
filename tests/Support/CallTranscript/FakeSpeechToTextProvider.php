<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextException;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextProviderInterface;

final class FakeSpeechToTextProvider implements SpeechToTextProviderInterface
{
    public const FAILING_AUDIO = 'fail';
    public const RATE_LIMITED_AUDIO = 'rate-limit';
    public const MP3_SEGMENT_START = 0.5;

    /**
     * @var list<array{audio: string, language: ?string}>
     */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    public function transcribe(string $audioPath, ?string $language): array
    {
        $audio = (string) file_get_contents($audioPath);
        self::$calls[] = ['audio' => $audio, 'language' => $language];

        if (trim($audio) === self::FAILING_AUDIO) {
            throw new SpeechToTextException('Fake provider failure.');
        }

        if (trim($audio) === self::RATE_LIMITED_AUDIO) {
            throw SpeechToTextException::temporarilyUnavailable('Fake rate limit.');
        }

        if (str_starts_with($audio, "\xFF\xFB")) {
            return [new TranscriptSegment(self::MP3_SEGMENT_START, sprintf('mp3 part of %d bytes', strlen($audio)))];
        }

        $segments = [];
        foreach (preg_split('/\R/u', trim($audio)) ?: [] as $line) {
            [$seconds, $text] = array_pad(explode('|', $line, 2), 2, '');
            $segments[] = new TranscriptSegment((float) $seconds, trim($text));
        }

        return $segments;
    }
}
