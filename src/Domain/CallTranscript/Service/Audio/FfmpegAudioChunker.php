<?php

namespace App\Domain\CallTranscript\Service\Audio;

use App\Domain\CallTranscript\DTO\AudioChunk;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final readonly class FfmpegAudioChunker implements AudioChunkerInterface
{
    public const TRANSCODED_BITRATE = '24k';
    public const SAMPLE_RATE = 16000;
    private const BUDGET_BITS_PER_SECOND = 32000;
    private const PROCESS_TIMEOUT_SECONDS = 3600;
    private const MAX_SEGMENT_ATTEMPTS = 4;

    public function __construct(
        private FfmpegLocator $ffmpegLocator,
    ) {
    }

    public function split(string $audioPath, string $workDirectory, int $maxBytes): array
    {
        $ffmpeg = $this->ffmpegLocator->find();
        if ($ffmpeg === null) {
            throw AudioChunkingException::ffmpegFailed('ffmpeg is not installed.');
        }

        $transcodedPath = $workDirectory . '/transcoded.ogg';
        $this->run([
            $ffmpeg, '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
            '-i', $audioPath,
            '-vn', '-ac', '1', '-ar', (string) self::SAMPLE_RATE,
            '-c:a', 'libopus', '-b:a', self::TRANSCODED_BITRATE, '-application', 'voip',
            $transcodedPath,
        ]);

        if ((int) filesize($transcodedPath) <= $maxBytes) {
            return [new AudioChunk($transcodedPath, 0.0, true)];
        }

        $segmentSeconds = max(1, intdiv($maxBytes * 8, self::BUDGET_BITS_PER_SECOND));
        for ($attempt = 1; ; ++$attempt) {
            $chunks = $this->segment($ffmpeg, $transcodedPath, $workDirectory, $segmentSeconds);
            $largestChunkBytes = max(array_map(static fn (AudioChunk $chunk): int => (int) filesize($chunk->path), $chunks));
            if ($largestChunkBytes <= $maxBytes) {
                @unlink($transcodedPath);

                return $chunks;
            }

            foreach ($chunks as $chunk) {
                @unlink($chunk->path);
            }

            if ($attempt >= self::MAX_SEGMENT_ATTEMPTS || $segmentSeconds === 1) {
                throw AudioChunkingException::chunkTooLarge($largestChunkBytes, $maxBytes);
            }

            $segmentSeconds = max(1, (int) floor($segmentSeconds * $maxBytes / $largestChunkBytes * 0.9));
        }
    }

    /**
     * @return non-empty-list<AudioChunk>
     */
    private function segment(string $ffmpeg, string $transcodedPath, string $workDirectory, int $segmentSeconds): array
    {
        $segmentListPath = $workDirectory . '/segments.csv';
        $this->run([
            $ffmpeg, '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
            '-i', $transcodedPath,
            '-f', 'segment', '-segment_time', (string) $segmentSeconds,
            '-segment_list', $segmentListPath, '-segment_list_type', 'csv',
            '-reset_timestamps', '1', '-c', 'copy',
            $workDirectory . '/part-%03d.ogg',
        ]);

        $chunks = [];
        foreach (file($segmentListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $row) {
            [$filename, $start] = array_pad(str_getcsv($row, ',', '"', ''), 2, '0');
            $chunks[] = new AudioChunk($workDirectory . '/' . basename((string) $filename), (float) $start, true);
        }
        @unlink($segmentListPath);

        if ($chunks === []) {
            throw AudioChunkingException::ffmpegFailed('ffmpeg produced no audio parts.');
        }

        return $chunks;
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command): void
    {
        $process = new Process($command, null, null, null, self::PROCESS_TIMEOUT_SECONDS);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $failure) {
            throw AudioChunkingException::ffmpegFailed($process->getErrorOutput(), $failure);
        }
    }
}
