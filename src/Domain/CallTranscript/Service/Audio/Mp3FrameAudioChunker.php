<?php

namespace App\Domain\CallTranscript\Service\Audio;

use App\Domain\CallTranscript\DTO\AudioChunk;

final class Mp3FrameAudioChunker implements AudioChunkerInterface
{
    private const MPEG1 = 3;
    private const LAYER_1 = 3;
    private const LAYER_2 = 2;
    private const LAYER_3 = 1;
    private const ID3V2_HEADER_BYTES = 10;
    private const MAX_RESYNC_BYTES = 64 * 1024;

    private const BITRATES = [
        'mpeg1' => [
            self::LAYER_1 => [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448],
            self::LAYER_2 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384],
            self::LAYER_3 => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320],
        ],
        'mpeg2' => [
            self::LAYER_1 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256],
            self::LAYER_2 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160],
            self::LAYER_3 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160],
        ],
    ];

    private const SAMPLE_RATES = [
        3 => [44100, 48000, 32000],
        2 => [22050, 24000, 16000],
        0 => [11025, 12000, 8000],
    ];

    public static function supports(string $audioPath): bool
    {
        return strtolower(pathinfo($audioPath, PATHINFO_EXTENSION)) === 'mp3';
    }

    public function split(string $audioPath, string $workDirectory, int $maxBytes): array
    {
        $input = @fopen($audioPath, 'rb');
        if ($input === false) {
            throw AudioChunkingException::unreadable($audioPath);
        }

        $chunks = [];
        $output = null;
        $chunkBytes = 0;
        $elapsedSeconds = 0.0;
        $framesFound = 0;

        try {
            $position = $this->audioStart($input);
            $skippedBytes = 0;
            while (true) {
                fseek($input, $position);
                $header = fread($input, 4);
                if ($header === false || strlen($header) < 4) {
                    break;
                }

                $frame = $this->parseHeader($header);
                if ($frame === null) {
                    if (++$skippedBytes > self::MAX_RESYNC_BYTES) {
                        break;
                    }
                    ++$position;
                    continue;
                }

                $skippedBytes = 0;
                fseek($input, $position);
                $frameBytes = fread($input, $frame['length']);
                if ($frameBytes === false || strlen($frameBytes) < $frame['length']) {
                    break;
                }

                $position += $frame['length'];
                if ($this->isVbrInfoFrame($frameBytes, $frame)) {
                    continue;
                }

                if ($output === null || $chunkBytes + $frame['length'] > $maxBytes) {
                    if ($output !== null) {
                        fclose($output);
                    }

                    if ($frame['length'] > $maxBytes) {
                        throw AudioChunkingException::chunkTooLarge($frame['length'], $maxBytes);
                    }

                    $chunkPath = sprintf('%s/part-%03d.mp3', $workDirectory, count($chunks));
                    $output = fopen($chunkPath, 'wb');
                    if ($output === false) {
                        throw AudioChunkingException::unreadable($chunkPath);
                    }

                    $chunks[] = new AudioChunk($chunkPath, $elapsedSeconds, true);
                    $chunkBytes = 0;
                }

                fwrite($output, $frameBytes);
                $chunkBytes += $frame['length'];
                $elapsedSeconds += $frame['samples'] / $frame['sampleRate'];
                ++$framesFound;
            }
        } finally {
            fclose($input);
            if (is_resource($output)) {
                fclose($output);
            }
        }

        if ($framesFound === 0) {
            foreach ($chunks as $chunk) {
                @unlink($chunk->path);
            }

            throw AudioChunkingException::noMp3Frames();
        }

        return $chunks;
    }

    /**
     * @param resource $input
     */
    private function audioStart($input): int
    {
        $header = fread($input, self::ID3V2_HEADER_BYTES);
        if ($header === false || strlen($header) < self::ID3V2_HEADER_BYTES || !str_starts_with($header, 'ID3')) {
            return 0;
        }

        $size = ((ord($header[6]) & 0x7F) << 21) | ((ord($header[7]) & 0x7F) << 14) | ((ord($header[8]) & 0x7F) << 7) | (ord($header[9]) & 0x7F);
        $hasFooter = (ord($header[5]) & 0x10) !== 0;

        return self::ID3V2_HEADER_BYTES + $size + ($hasFooter ? self::ID3V2_HEADER_BYTES : 0);
    }

    /**
     * @return array{length: int, samples: int, sampleRate: int, version: int, channelMode: int}|null
     */
    private function parseHeader(string $header): ?array
    {
        $first = ord($header[0]);
        $second = ord($header[1]);
        $third = ord($header[2]);
        $fourth = ord($header[3]);
        if ($first !== 0xFF || ($second & 0xE0) !== 0xE0) {
            return null;
        }

        $version = ($second >> 3) & 0x03;
        $layer = ($second >> 1) & 0x03;
        $bitrateIndex = ($third >> 4) & 0x0F;
        $sampleRateIndex = ($third >> 2) & 0x03;
        if ($version === 1 || $layer === 0 || $bitrateIndex === 0 || $bitrateIndex === 15 || $sampleRateIndex === 3) {
            return null;
        }

        $padding = ($third >> 1) & 0x01;
        $bitrate = self::BITRATES[$version === self::MPEG1 ? 'mpeg1' : 'mpeg2'][$layer][$bitrateIndex] * 1000;
        $sampleRate = self::SAMPLE_RATES[$version][$sampleRateIndex];

        if ($layer === self::LAYER_1) {
            $length = (intdiv(12 * $bitrate, $sampleRate) + $padding) * 4;
            $samples = 384;
        } elseif ($layer === self::LAYER_3 && $version !== self::MPEG1) {
            $length = intdiv(72 * $bitrate, $sampleRate) + $padding;
            $samples = 576;
        } else {
            $length = intdiv(144 * $bitrate, $sampleRate) + $padding;
            $samples = 1152;
        }

        return [
            'length' => $length,
            'samples' => $samples,
            'sampleRate' => $sampleRate,
            'version' => $version,
            'channelMode' => ($fourth >> 6) & 0x03,
        ];
    }

    /**
     * @param array{length: int, samples: int, sampleRate: int, version: int, channelMode: int} $frame
     */
    private function isVbrInfoFrame(string $frameBytes, array $frame): bool
    {
        $mono = $frame['channelMode'] === 3;
        $sideInformationBytes = $frame['version'] === self::MPEG1 ? ($mono ? 17 : 32) : ($mono ? 9 : 17);
        $tag = substr($frameBytes, 4 + $sideInformationBytes, 4);

        return in_array($tag, ['Xing', 'Info'], true) || substr($frameBytes, 36, 4) === 'VBRI';
    }
}
