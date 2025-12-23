<?php

declare(strict_types=1);

namespace Tests\Integration\Mailing;

use App\Domain\Core\Entity\Larp;
use App\Domain\Mailing\Dto\MailTemplateDefinition;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Repository\MailTemplateRepository;
use App\Domain\Mailing\Service\MailTemplateDefinitionProvider;
use App\Domain\Mailing\Service\MailTemplateManager;
use Codeception\Test\Unit;
use Tests\Support\FunctionalTester;

class MailTemplateManagerCest extends Unit
{
    public function ensureTemplatesCreatesMissingOnes(FunctionalTester $I): void
    {
        $I->wantTo('verify that ensureTemplatesForLarp creates missing templates');

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
        $repository->expects($this->once())->method('findBy')->with(['larp' => $larp])->willReturn([]);
        $repository->expects($this->once())->method('save');
        $repository->expects($this->once())->method('flush');

        $manager = new MailTemplateManager($repository, $provider);
        $manager->ensureTemplatesForLarp($larp);
    }
}
