<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\SharedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\ExternalReferenceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\IntegrationFixtureRouters;

class IntegrationTestSupportCest
{
    public function factoriesPersistIntegrationGraph(FunctionalTester $I): void
    {
        $I->wantTo('persist an integration with shared files and an external reference using factories');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->create();
        SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->create();
        SharedFileFactory::new()->forIntegration($integration)->document()->readOnly()->create();
        $character = CharacterFactory::new()->forLarp($larp)->create();
        ExternalReferenceFactory::new()
            ->forStoryObject($character)
            ->createdBy($organizer)
            ->withReferenceType(ReferenceType::Spreadsheet)
            ->create();

        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        $storedIntegration = $entityManager->getRepository(LarpIntegration::class)->find($integration->getId());
        $I->assertNotNull($storedIntegration);
        $I->assertSame($larp->getId()->toRfc4122(), $storedIntegration->getLarp()->getId()->toRfc4122());
        $I->assertCount(2, $entityManager->getRepository(SharedFile::class)->findBy(['integration' => $storedIntegration]));

        $references = $entityManager->getRepository(ExternalReference::class)->findBy(['storyObject' => $character->getId()]);
        $I->assertCount(1, $references);
        $I->assertSame(ReferenceType::Spreadsheet, $references[0]->getReferenceType());
    }

    public function scopedClientsServeFixturesInTestEnvironment(FunctionalTester $I): void
    {
        $I->wantTo('verify discord.client and miro.client are fixture-backed mocks in the test env');

        /** @var HttpClientInterface $discordClient */
        $discordClient = $I->grabService('discord.client');
        /** @var HttpClientInterface $miroClient */
        $miroClient = $I->grabService('miro.client');

        $guild = $discordClient->request('GET', 'guilds/' . IntegrationFixtureRouters::DISCORD_GUILD_ID)->toArray();
        $boards = $miroClient->request('GET', 'boards')->toArray();

        $I->assertSame(IntegrationFixtureRouters::DISCORD_GUILD_ID, $guild['id']);
        $I->assertSame(IntegrationFixtureRouters::MIRO_BOARD_ID, $boards['data'][0]['id']);
    }
}
