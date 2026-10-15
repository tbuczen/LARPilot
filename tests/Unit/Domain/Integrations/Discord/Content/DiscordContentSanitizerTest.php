<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Discord\Content;

use App\Domain\Integrations\Discord\Content\DiscordContentSanitizer;
use PHPUnit\Framework\TestCase;

class DiscordContentSanitizerTest extends TestCase
{
    public function testReplacesMentionsAndStripsMarkdown(): void
    {
        $sanitized = DiscordContentSanitizer::sanitize(
            "## Plan\n**Bold** and *italic* for <@!42>, <@7>, <@&9> in <#11> with <:seal:123> ||spoiler|| `code` <https://example.test/a>",
            ['42' => 'Game Master'],
        );

        self::assertSame("Plan\nBold and italic for @Game Master, @user, @role in #channel with :seal: spoiler code https://example.test/a", $sanitized);
    }

    public function testRendersDiscordTimestampsInTheDisplayFormat(): void
    {
        self::assertSame('Meet at 22-09-2026 03:33', DiscordContentSanitizer::sanitize('Meet at <t:1790048000:F>'));
    }

    public function testRemovesHtmlAndTruncates(): void
    {
        $sanitized = DiscordContentSanitizer::sanitize('<script>alert(1)</script>' . str_repeat('a', 50), [], 20);

        self::assertStringNotContainsString('<script>', $sanitized);
        self::assertSame(20, mb_strlen($sanitized));
        self::assertStringEndsWith('…', $sanitized);
    }
}
