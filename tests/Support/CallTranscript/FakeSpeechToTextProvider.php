<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextException;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextProviderInterface;

final class FakeSpeechToTextProvider implements SpeechToTextProviderInterface
{
    public const FAILING_AUDIO = 'fail';

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

        $segments = [];
        foreach (preg_split('/\R/', trim($audio)) ?: [] as $line) {
            [$seconds, $text] = array_pad(explode('|', $line, 2), 2, '');
            $segments[] = new TranscriptSegment((float) $seconds, trim($text));
        }

        return $segments;
    }
}
