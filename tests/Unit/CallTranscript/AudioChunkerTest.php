<?php

declare(strict_types=1);

namespace Tests\Unit\CallTranscript;

use App\Domain\CallTranscript\DTO\AudioChunk;
use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Service\Audio\AudioChunkingException;
use App\Domain\CallTranscript\Service\Audio\FfmpegAudioChunker;
use App\Domain\CallTranscript\Service\Audio\FfmpegLocator;
use App\Domain\CallTranscript\Service\Audio\Mp3FrameAudioChunker;
use App\Domain\CallTranscript\Service\Audio\SizeLimitedAudioChunker;
use App\Domain\CallTranscript\Service\SpeechToText\CallTrackAudioTranscriber;
use App\Domain\CallTranscript\Service\SpeechToText\SpeechToTextProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\Support\CallTranscript\Mp3FrameBuilder;

class AudioChunkerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/audio-chunker-' . bin2hex(random_bytes(6));
        (new Filesystem())->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testMp3IsSplitOnFrameBoundariesWithTimeOffsets(): void
    {
        $audioPath = $this->writeAudio('call.mp3', Mp3FrameBuilder::build(100));
        $maxBytes = 10 * Mp3FrameBuilder::FRAME_BYTES + 100;

        $chunks = (new Mp3FrameAudioChunker())->split($audioPath, $this->directory, $maxBytes);

        $this->assertCount(10, $chunks);
        foreach ($chunks as $index => $chunk) {
            $this->assertTrue($chunk->temporary);
            $this->assertSame(10 * Mp3FrameBuilder::FRAME_BYTES, filesize($chunk->path));
            $this->assertEqualsWithDelta($index * 10 * Mp3FrameBuilder::frameSeconds(), $chunk->offsetSeconds, 0.0001);
            $this->assertSame("\xFF\xFB", substr((string) file_get_contents($chunk->path), 0, 2));
        }
    }

    public function testMp3SplitSkipsTagsVbrHeaderAndJunk(): void
    {
        $audioPath = $this->writeAudio('call.mp3', Mp3FrameBuilder::build(5, junkBetweenFrames: "\x00\x12\xFF\x00junk"));

        $chunks = (new Mp3FrameAudioChunker())->split($audioPath, $this->directory, 1024 * 1024);

        $this->assertCount(1, $chunks);
        $this->assertSame(5 * Mp3FrameBuilder::FRAME_BYTES, filesize($chunks[0]->path));
        $this->assertStringNotContainsString('Xing', (string) file_get_contents($chunks[0]->path));
    }

    public function testFileWithoutMp3FramesIsRejected(): void
    {
        $audioPath = $this->writeAudio('call.mp3', str_repeat('not audio', 1000));

        $this->expectException(AudioChunkingException::class);
        (new Mp3FrameAudioChunker())->split($audioPath, $this->directory, 1024);
    }

    public function testSmallFileIsSentAsIs(): void
    {
        $audioPath = $this->writeAudio('call.flac', 'small');

        $chunks = $this->chunkerWithoutFfmpeg()->split($audioPath, $this->directory, 1024);

        $this->assertEquals([new AudioChunk($audioPath, 0.0, false)], $chunks);
    }

    public function testLargeMp3WithoutFfmpegUsesFrameSplitter(): void
    {
        $audioPath = $this->writeAudio('call.mp3', Mp3FrameBuilder::build(30));

        $chunks = $this->chunkerWithoutFfmpeg()->split($audioPath, $this->directory, 10 * Mp3FrameBuilder::FRAME_BYTES);

        $this->assertCount(3, $chunks);
    }

    public function testLargeNonMp3WithoutFfmpegIsLeftForTheProviderToReject(): void
    {
        $audioPath = $this->writeAudio('call.flac', str_repeat('a', 5000));

        $chunks = $this->chunkerWithoutFfmpeg()->split($audioPath, $this->directory, 1024);

        $this->assertEquals([new AudioChunk($audioPath, 0.0, false)], $chunks);
    }

    public function testTranscriberShiftsTimestampsByChunkOffset(): void
    {
        $audioPath = $this->writeAudio('call.mp3', Mp3FrameBuilder::build(30));
        $provider = new class() implements SpeechToTextProviderInterface {
            public int $calls = 0;

            public function transcribe(string $audioPath, ?string $language): array
            {
                ++$this->calls;

                return [new TranscriptSegment(1.0, 'part ' . $this->calls)];
            }
        };
        $transcriber = new CallTrackAudioTranscriber(
            new class($this->chunkerWithoutFfmpeg()) implements \App\Domain\CallTranscript\Service\Audio\AudioChunkerInterface {
                public function __construct(private readonly SizeLimitedAudioChunker $inner)
                {
                }

                public function split(string $audioPath, string $workDirectory, int $maxBytes): array
                {
                    return $this->inner->split($audioPath, $workDirectory, 10 * Mp3FrameBuilder::FRAME_BYTES);
                }
            },
            $provider,
        );
        $workDirectory = $this->directory . '/work';

        $segments = $transcriber->transcribe($audioPath, 'pl', $workDirectory);

        $this->assertCount(3, $segments);
        $this->assertEqualsWithDelta(1.0, $segments[0]->startSeconds, 0.0001);
        $this->assertEqualsWithDelta(1.0 + 10 * Mp3FrameBuilder::frameSeconds(), $segments[1]->startSeconds, 0.0001);
        $this->assertEqualsWithDelta(1.0 + 20 * Mp3FrameBuilder::frameSeconds(), $segments[2]->startSeconds, 0.0001);
        $this->assertSame('part 3', $segments[2]->text);
        $this->assertDirectoryDoesNotExist($workDirectory);
        $this->assertFileExists($audioPath);
    }

    public function testFfmpegTranscodesAndSplitsLongAudio(): void
    {
        $locator = new FfmpegLocator();
        $ffmpeg = $locator->find();
        if ($ffmpeg === null) {
            $this->markTestSkipped('ffmpeg is not installed.');
        }

        $audioPath = $this->directory . '/call.wav';
        (new Process([$ffmpeg, '-nostdin', '-loglevel', 'error', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=40', $audioPath]))->mustRun();

        $chunks = (new FfmpegAudioChunker($locator))->split($audioPath, $this->directory, 40000);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame(0.0, $chunks[0]->offsetSeconds);
        $previousOffset = -1.0;
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(40000, filesize($chunk->path));
            $this->assertGreaterThan($previousOffset, $chunk->offsetSeconds);
            $previousOffset = $chunk->offsetSeconds;
        }
        $this->assertLessThan(40.0, $chunks[array_key_last($chunks)]->offsetSeconds);
    }

    private function chunkerWithoutFfmpeg(): SizeLimitedAudioChunker
    {
        $locator = new FfmpegLocator(false);

        return new SizeLimitedAudioChunker($locator, new FfmpegAudioChunker($locator), new Mp3FrameAudioChunker());
    }

    private function writeAudio(string $filename, string $content): string
    {
        $path = $this->directory . '/' . $filename;
        file_put_contents($path, $content);

        return $path;
    }
}
