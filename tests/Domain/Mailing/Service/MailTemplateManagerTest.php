<?php

namespace App\Tests\Domain\Mailing\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Mailing\Dto\MailTemplateDefinition;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Repository\MailTemplateRepository;
use App\Domain\Mailing\Service\MailTemplateDefinitionProvider;
use App\Domain\Mailing\Service\MailTemplateManager;
use PHPUnit\Framework\TestCase;

class MailTemplateManagerTest extends TestCase
{
    public function testEnsureTemplatesCreatesMissingOnes(): void
    {
        $larp = new Larp();
        $larp->setTitle('Test');
        $larp->setDescription('desc');

        $definition = new MailTemplateDefinition(
            MailTemplateType::ENQUIRY_OPEN,
            'test',
            'desc',
            'subject',
            'body',
            ['placeholder'],
            true,
        );

        $provider = $this->createMock(MailTemplateDefinitionProvider::class);
        $provider->method('getDefinitions')->willReturn([
            MailTemplateType::ENQUIRY_OPEN->value => $definition,
        ]);
        $provider->method('getDefinition')->willReturn($definition);

        $repository = $this->createMock(MailTemplateRepository::class);
        $repository->expects(self::once())->method('findBy')->with(['larp' => $larp])->willReturn([]);
        $repository->expects(self::once())->method('save');
        $repository->expects(self::once())->method('flush');

        $manager = new MailTemplateManager($repository, $provider);
        $manager->ensureTemplatesForLarp($larp);
    }
}
