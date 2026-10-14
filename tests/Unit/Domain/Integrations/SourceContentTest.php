<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations;

use App\Domain\Integrations\DTO\SourceContent;
use App\Domain\Integrations\DTO\SourceContentChunk;
use PHPUnit\Framework\TestCase;

class SourceContentTest extends TestCase
{
    public function testJoinsChunksIntoPlainTextAndHashesIt(): void
    {
        $content = new SourceContent([
            new SourceContentChunk(0, 'First message', 'alice', new \DateTimeImmutable('2026-01-01 10:00'), 'https://example.test/1'),
            new SourceContentChunk(1, 'Second message', 'bob'),
        ], new \DateTimeImmutable('2026-01-01 11:00'));

        $this->assertSame("First message\n\nSecond message", $content->plainText);
        $this->assertSame(hash('sha256', "First message\n\nSecond message"), $content->contentHash);
        $this->assertFalse($content->isEmpty());
    }

    public function testSameTextGivesSameHash(): void
    {
        $firstContent = new SourceContent([new SourceContentChunk(0, 'Text', 'alice')]);
        $secondContent = new SourceContent([new SourceContentChunk(0, 'Text', 'bob')]);

        $this->assertSame($firstContent->contentHash, $secondContent->contentHash);
    }

    public function testEmptyContent(): void
    {
        $content = new SourceContent([]);

        $this->assertTrue($content->isEmpty());
        $this->assertSame('', $content->plainText);
    }

    public function testTitleAndTagsAreHashedWithTheChunks(): void
    {
        $chunks = [new SourceContentChunk(0, 'Text')];
        $digest = new SourceContent($chunks, null, 'Thread title', ['Main plot']);

        $this->assertSame('Text', $digest->plainText);
        $this->assertSame(hash('sha256', "Thread title\n\nMain plot\n\nText"), $digest->contentHash);
        $this->assertNotSame((new SourceContent($chunks, null, 'Renamed title', ['Main plot']))->contentHash, $digest->contentHash);
    }
}
