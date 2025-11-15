<?php

namespace App\Tests\Domain\Mailing\Service;

use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Service\MailTemplateDefinitionProvider;
use PHPUnit\Framework\TestCase;

class MailTemplateDefinitionProviderTest extends TestCase
{
    public function testReturnsDefinitionsForEveryType(): void
    {
        $provider = new MailTemplateDefinitionProvider();
        $definitions = $provider->getDefinitions();

        self::assertCount(count(MailTemplateType::cases()), $definitions);
        self::assertArrayHasKey(MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED->value, $definitions);
        $assignmentDefinition = $definitions[MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED->value];
        self::assertContains('character_public_url', $assignmentDefinition->placeholders);
    }
}
