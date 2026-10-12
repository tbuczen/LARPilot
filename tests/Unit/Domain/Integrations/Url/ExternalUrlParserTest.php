<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Url;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Service\Url\ExternalUrlParser;
use PHPUnit\Framework\TestCase;

class ExternalUrlParserTest extends TestCase
{
    private ExternalUrlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ExternalUrlParser();
    }

    public function testDiscordChannelLink(): void
    {
        $parsedUrl = $this->parser->parse('https://discord.com/channels/111111111111111111/222222222222222222');

        $this->assertNotNull($parsedUrl);
        $this->assertSame(LarpIntegrationProvider::Discord, $parsedUrl->provider);
        $this->assertSame('222222222222222222', $parsedUrl->externalId);
        $this->assertSame(ReferenceType::Thread, $parsedUrl->referenceType);
        $this->assertSame('https://discord.com/channels/111111111111111111/222222222222222222', $parsedUrl->url);
    }

    public function testDiscordMessageLink(): void
    {
        $parsedUrl = $this->parser->parse('  https://ptb.discord.com/channels/111111111111111111/222222222222222222/333333333333333333/  ');

        $this->assertNotNull($parsedUrl);
        $this->assertSame(LarpIntegrationProvider::Discord, $parsedUrl->provider);
        $this->assertSame('333333333333333333', $parsedUrl->externalId);
        $this->assertSame(ReferenceType::Comment, $parsedUrl->referenceType);
        $this->assertSame('https://discord.com/channels/111111111111111111/222222222222222222/333333333333333333', $parsedUrl->url);
    }

    public function testLegacyDiscordAppHost(): void
    {
        $parsedUrl = $this->parser->parse('https://discordapp.com/channels/111/222');

        $this->assertNotNull($parsedUrl);
        $this->assertSame('222', $parsedUrl->externalId);
    }

    public function testMiroBoardLink(): void
    {
        $parsedUrl = $this->parser->parse('https://miro.com/app/board/uXjVKabc123=/');

        $this->assertNotNull($parsedUrl);
        $this->assertSame(LarpIntegrationProvider::Miro, $parsedUrl->provider);
        $this->assertSame('uXjVKabc123=', $parsedUrl->externalId);
        $this->assertSame(ReferenceType::Board, $parsedUrl->referenceType);
        $this->assertSame('https://miro.com/app/board/uXjVKabc123=/', $parsedUrl->url);
    }

    public function testMiroBoardItemLink(): void
    {
        $parsedUrl = $this->parser->parse('https://miro.com/app/board/uXjVKabc123=/?moveToWidget=3458764512345678901&cot=14');

        $this->assertNotNull($parsedUrl);
        $this->assertSame(LarpIntegrationProvider::Miro, $parsedUrl->provider);
        $this->assertSame('3458764512345678901', $parsedUrl->externalId);
        $this->assertSame(ReferenceType::BoardItem, $parsedUrl->referenceType);
        $this->assertSame('https://miro.com/app/board/uXjVKabc123=/?moveToWidget=3458764512345678901', $parsedUrl->url);
    }

    public function testMiroLinkWithInvalidWidgetFallsBackToBoard(): void
    {
        $parsedUrl = $this->parser->parse('https://miro.com/app/board/uXjVKabc123=/?moveToWidget=<script>');

        $this->assertNotNull($parsedUrl);
        $this->assertSame('uXjVKabc123=', $parsedUrl->externalId);
        $this->assertSame(ReferenceType::Board, $parsedUrl->referenceType);
    }

    /**
     * @dataProvider unsupportedUrls
     */
    public function testUnsupportedUrlsAreRejected(string $url): void
    {
        $this->assertNull($this->parser->parse($url));
    }

    public static function unsupportedUrls(): iterable
    {
        yield 'empty' => [''];
        yield 'not a url' => ['discord channel 123'];
        yield 'other host' => ['https://example.com/channels/111/222'];
        yield 'lookalike host' => ['https://discord.com.evil.test/channels/111/222'];
        yield 'discord invite' => ['https://discord.com/invite/abc'];
        yield 'discord non numeric ids' => ['https://discord.com/channels/abc/def'];
        yield 'discord direct messages' => ['https://discord.com/channels/@me/222'];
        yield 'javascript scheme' => ['javascript://discord.com/channels/111/222'];
        yield 'miro dashboard' => ['https://miro.com/app/dashboard/'];
        yield 'google doc' => ['https://docs.google.com/document/d/abc/edit'];
    }
}
