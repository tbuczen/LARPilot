<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations;

use App\Domain\Integrations\Service\ExcerptNormalizer;
use PHPUnit\Framework\TestCase;

class ExcerptNormalizerTest extends TestCase
{
    public function testNullStaysNull(): void
    {
        $this->assertNull(ExcerptNormalizer::normalize(null));
    }

    public function testBlankTextBecomesNull(): void
    {
        $this->assertNull(ExcerptNormalizer::normalize("  <p> </p>\n\t"));
    }

    public function testStripsMarkupAndCollapsesWhitespace(): void
    {
        $excerpt = ExcerptNormalizer::normalize("<p>Hello <script>alert(1)</script><b>world</b></p>\n\n &amp; more\x07");

        $this->assertSame('Hello alert(1)world & more', $excerpt);
    }

    public function testTruncatesToMaximumLength(): void
    {
        $excerpt = ExcerptNormalizer::normalize(str_repeat('ąbc ', 300));

        $this->assertNotNull($excerpt);
        $this->assertSame(ExcerptNormalizer::MAX_LENGTH, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
    }

    public function testKeepsShortTextIntact(): void
    {
        $this->assertSame('Short note', ExcerptNormalizer::normalize('Short note'));
    }
}
