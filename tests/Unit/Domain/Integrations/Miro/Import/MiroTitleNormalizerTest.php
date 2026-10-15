<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Import\MiroTitleNormalizer;
use Codeception\Test\Unit;

class MiroTitleNormalizerTest extends Unit
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizedTitles(): iterable
    {
        yield 'case and surrounding spaces' => ['  Captain MIRA Vale ', 'captain mira vale'];
        yield 'accents' => ['Capitaine Éloïse Çavaré', 'capitaine eloise cavare'];
        yield 'polish letters' => ['Żółta Łódź Gęśli', 'zolta lodz gesli'];
        yield 'german letters' => ['Großer Bär', 'grosser bar'];
        yield 'punctuation and dashes' => ["Smugglers' Guild — Harbour-Watch!", 'smugglers guild harbour watch'];
        yield 'html and entities' => ['<p>Lady&nbsp;<strong>Morwen</strong></p>', 'lady morwen'];
        yield 'line breaks and tabs' => ["House\n\tArdenne", 'house ardenne'];
        yield 'digits kept' => ['Act 2: The Council', 'act 2 the council'];
    }

    /**
     * @dataProvider normalizedTitles
     */
    public function testNormalizesForMatching(string $title, string $expected): void
    {
        $this->assertSame($expected, MiroTitleNormalizer::normalize($title));
    }

    public function testNormalizesEmptyAndNullToEmptyString(): void
    {
        $this->assertSame('', MiroTitleNormalizer::normalize(null));
        $this->assertSame('', MiroTitleNormalizer::normalize(' — ! '));
    }

    public function testFoldedTitlesMatchTheirAccentedVariants(): void
    {
        $this->assertSame(MiroTitleNormalizer::normalize('Captain Mira Valé'), MiroTitleNormalizer::normalize('captain mira vale'));
        $this->assertSame(MiroTitleNormalizer::normalize('Przyjaźń'), MiroTitleNormalizer::normalize('PRZYJAZN'));
    }

    public function testExactKeepsCaseAndAccentsButCollapsesWhitespace(): void
    {
        $this->assertSame('Captain Mira Valé', MiroTitleNormalizer::exact("  Captain \n Mira   Valé "));
        $this->assertSame('Lady Morwen', MiroTitleNormalizer::exact('<p>Lady <em>Morwen</em></p>'));
        $this->assertNotSame(MiroTitleNormalizer::exact('Captain Mira Vale'), MiroTitleNormalizer::exact('Captain Mira Valé'));
        $this->assertNotSame(MiroTitleNormalizer::exact('brother tomas'), MiroTitleNormalizer::exact('Brother Tomas'));
    }
}
