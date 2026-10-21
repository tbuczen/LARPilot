<?php

declare(strict_types=1);

namespace Tests\Unit\CallTranscript;

use App\Domain\CallTranscript\DTO\CallSummary;
use App\Domain\CallTranscript\Service\Summary\CallSummaryException;
use App\Domain\CallTranscript\Service\Summary\CallSummaryGenerator;
use PHPUnit\Framework\TestCase;
use Tests\Support\CallTranscript\FakeLlmProvider;

class CallSummaryGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        FakeLlmProvider::reset();
    }

    public function testShortTranscriptIsSummarizedInOneCall(): void
    {
        FakeLlmProvider::respondWith([
            'summary' => 'Ustalono zdradę.',
            'decisions' => ['Kruki zdradzają króla', ''],
            'action_items' => [['task' => 'Napisać wstęp', 'person' => 'Anna'], ['task' => 'Sprawdzić mapę', 'person' => null], 'Zamówić stroje'],
            'open_questions' => ['Kiedy?'],
            'mentioned' => ['Kruki'],
        ]);

        $summary = (new CallSummaryGenerator(new FakeLlmProvider()))->generate(['[00:00:01] anna: Kruki zdradzą'], 'pl', ['Kruki', 'Król']);

        $this->assertEquals(new CallSummary(
            'Ustalono zdradę.',
            ['Kruki zdradzają króla'],
            [['task' => 'Napisać wstęp', 'person' => 'Anna'], ['task' => 'Sprawdzić mapę', 'person' => null], ['task' => 'Zamówić stroje', 'person' => null]],
            ['Kiedy?'],
            ['Kruki'],
        ), $summary);
        $this->assertCount(1, FakeLlmProvider::$calls);
        $this->assertStringContainsString('Write in Polish.', FakeLlmProvider::$calls[0][0]->content);
        $this->assertStringContainsString('Known names in this game: Kruki; Król', FakeLlmProvider::$calls[0][0]->content);
        $this->assertSame('[00:00:01] anna: Kruki zdradzą', FakeLlmProvider::$calls[0][1]->content);
    }

    public function testLongTranscriptIsSummarizedPerPartThenMerged(): void
    {
        FakeLlmProvider::$contextLength = 2000;
        $generator = new CallSummaryGenerator(new FakeLlmProvider());
        $lines = array_map(static fn (int $index): string => sprintf('[00:%02d:00] anna: %s', $index % 60, str_repeat('x', 90)), range(1, 80));

        $summary = $generator->generate($lines, null);

        $this->assertSame(3200, $generator->chunkCharacters());
        $this->assertCount(4, FakeLlmProvider::$calls);
        $this->assertStringContainsString('This is part 1 of 3 of a longer call.', FakeLlmProvider::$calls[0][0]->content);
        $this->assertStringContainsString('This is part 3 of 3 of a longer call.', FakeLlmProvider::$calls[2][0]->content);
        foreach (array_slice(FakeLlmProvider::$calls, 0, 3) as $call) {
            $this->assertLessThanOrEqual(3200, mb_strlen($call[1]->content));
        }
        $this->assertStringContainsString('partial summaries', FakeLlmProvider::$calls[3][0]->content);
        $this->assertStringContainsString('if unsure, write in Polish', FakeLlmProvider::$calls[3][0]->content);
        $this->assertCount(3, explode("\n", FakeLlmProvider::$calls[3][1]->content));
        $this->assertSame('Summary 4.', $summary->summary);
    }

    public function testInvalidModelResponseIsReported(): void
    {
        FakeLlmProvider::$responses[] = 'not json';

        $this->expectException(CallSummaryException::class);
        (new CallSummaryGenerator(new FakeLlmProvider()))->generate(['[00:00:01] anna: Hej'], 'en');
    }

    public function testProviderFailureIsReported(): void
    {
        FakeLlmProvider::$failure = 'Rate limit reached';

        $this->expectExceptionMessage('The AI model failed: Rate limit reached');
        (new CallSummaryGenerator(new FakeLlmProvider()))->generate(['[00:00:01] anna: Hej'], 'en');
    }
}
