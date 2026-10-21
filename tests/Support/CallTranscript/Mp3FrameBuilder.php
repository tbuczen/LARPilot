<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

final class Mp3FrameBuilder
{
    public const FRAME_BYTES = 417;
    public const SAMPLES_PER_FRAME = 1152;
    public const SAMPLE_RATE = 44100;

    public static function build(int $frames, bool $withId3 = true, bool $withXing = true, string $junkBetweenFrames = ''): string
    {
        $audio = $withId3 ? self::id3Tag() : '';
        if ($withXing) {
            $audio .= self::frame(0, 'Xing');
        }

        for ($index = 0; $index < $frames; ++$index) {
            $audio .= self::frame($index);
            if ($index === 0) {
                $audio .= $junkBetweenFrames;
            }
        }

        return $audio . 'TAG' . str_repeat("\0", 125);
    }

    public static function frameSeconds(): float
    {
        return self::SAMPLES_PER_FRAME / self::SAMPLE_RATE;
    }

    private static function frame(int $index, string $tag = ''): string
    {
        $header = "\xFF\xFB\x90\x64";
        $body = str_repeat("\0", 32) . $tag;
        $body .= str_repeat(chr($index % 256), self::FRAME_BYTES - 4 - strlen($body));

        return $header . $body;
    }

    private static function id3Tag(): string
    {
        $payload = str_repeat('x', 300);
        $size = strlen($payload);
        $syncSafe = chr(($size >> 21) & 0x7F) . chr(($size >> 14) & 0x7F) . chr(($size >> 7) & 0x7F) . chr($size & 0x7F);

        return "ID3\x04\x00\x00" . $syncSafe . $payload;
    }
}
