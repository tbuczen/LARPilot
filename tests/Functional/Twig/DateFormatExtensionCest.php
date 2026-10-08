<?php

declare(strict_types=1);

namespace Tests\Functional\Twig;

use Tests\Support\FunctionalTester;
use Twig\Environment;

class DateFormatExtensionCest
{
    public function displayFiltersUseSharedFormats(FunctionalTester $I): void
    {
        $I->wantTo('verify the display_* Twig filters and the default |date format render day-first');

        /** @var Environment $twig */
        $twig = $I->grabService('twig');
        $template = $twig->createTemplate(
            '{{ value|display_date }}|{{ value|display_datetime }}|{{ value|display_time }}|{{ value|date }}|{{ stored|display_datetime }}'
        );

        $rendered = $template->render([
            'value' => new \DateTimeImmutable('2026-06-12 18:05:42'),
            'stored' => '2026-06-14 16:00:00',
        ]);

        $I->assertSame('12-06-2026|12-06-2026 18:05|18:05|12-06-2026 18:05|14-06-2026 16:00', $rendered);
    }
}
