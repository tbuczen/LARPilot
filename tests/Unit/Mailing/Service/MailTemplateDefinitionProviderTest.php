<?php

declare(strict_types=1);

namespace Tests\Unit\Mailing\Service;

use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Service\MailTemplateDefinitionProvider;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

class MailTemplateDefinitionProviderTest extends Unit
{
    public function definitionsExistForEveryType(UnitTester $I): void
    {
        $I->wantTo('verify that definitions exist for every mail template type');

        $provider = new MailTemplateDefinitionProvider();
        $definitions = $provider->getDefinitions();

        $I->assertCount(count(MailTemplateType::cases()), $definitions);
        $I->assertArrayHasKey(MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED->value, $definitions);

        $assignmentDefinition = $definitions[MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED->value];
        $I->assertContains('character_public_url', $assignmentDefinition->placeholders);
    }
}
