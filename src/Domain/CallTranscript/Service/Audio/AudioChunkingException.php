<?php

namespace App\Domain\CallTranscript\Service\Audio;

use RuntimeException;
use Throwable;

final class AudioChunkingException extends RuntimeException
{
    public static function unreadable(string $path): self
    {
        return new self(sprintf('Audio file "%s" could not be read.', basename($path)));
    }

    public static function noMp3Frames(): self
    {
        return new self('The file contains no MP3 audio frames.');
    }

    public static function chunkTooLarge(int $bytes, int $limit): self
    {
        return new self(sprintf('An audio part is still %d bytes after splitting, the limit is %d bytes.', $bytes, $limit));
    }

    public static function ffmpegFailed(string $output, ?Throwable $previous = null): self
    {
        return new self('ffmpeg could not convert the audio: ' . trim(mb_substr($output, -500)), 0, $previous);
    }
}
