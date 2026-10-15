<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Core\Service\Encryption\SecretBoxCipher;
use App\Domain\Integrations\Command\EncryptIntegrationTokensCommand;
use App\Domain\Integrations\Entity\LarpIntegration;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;

class IntegrationTokenEncryptionCest
{
    public function tokensAreStoredAsCiphertext(FunctionalTester $I): void
    {
        $I->wantTo('see integration tokens stored as v1: ciphertext and read back as plaintext');

        $integration = $this->createIntegration('stored-access-token', 'stored-refresh-token');

        $storedTokens = $this->fetchStoredTokens($I, $integration);
        $I->assertStringStartsWith('v1:', $storedTokens['access_token']);
        $I->assertStringStartsWith('v1:', $storedTokens['refresh_token']);
        $I->assertStringNotContainsString('stored-access-token', $storedTokens['access_token']);

        /** @var SecretBoxCipher $cipher */
        $cipher = $I->grabService(SecretBoxCipher::class);
        $I->assertSame('stored-refresh-token', $cipher->decrypt($storedTokens['refresh_token']));

        $I->getEntityManager()->clear();
        $reloadedIntegration = $I->getEntityManager()->find(LarpIntegration::class, $integration->getId());
        $I->assertSame('stored-access-token', $reloadedIntegration->getAccessToken());
        $I->assertSame('stored-refresh-token', $reloadedIntegration->getRefreshToken());
    }

    public function legacyPlaintextTokensAreReadableAndBackfilled(FunctionalTester $I): void
    {
        $I->wantTo('read legacy plaintext tokens and encrypt them with the backfill command');

        $integration = $this->createIntegration('placeholder', 'placeholder');
        $connection = $I->getEntityManager()->getConnection();
        $connection->update(
            'larp_integration',
            ['access_token' => 'legacy-access-token', 'refresh_token' => 'legacy-refresh-token'],
            ['id' => $integration->getId()->toRfc4122()],
        );

        $I->getEntityManager()->clear();
        $legacyIntegration = $I->getEntityManager()->find(LarpIntegration::class, $integration->getId());
        $I->assertSame('legacy-access-token', $legacyIntegration->getAccessToken());
        $I->assertSame('legacy-refresh-token', $legacyIntegration->getRefreshToken());

        $commandTester = new CommandTester($I->grabService(EncryptIntegrationTokensCommand::class));

        $commandTester->execute(['--dry-run' => true]);
        $commandTester->assertCommandIsSuccessful();
        $I->assertSame('legacy-access-token', $this->fetchStoredTokens($I, $integration)['access_token']);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $backfilledTokens = $this->fetchStoredTokens($I, $integration);
        $I->assertStringStartsWith('v1:', $backfilledTokens['access_token']);
        $I->assertStringStartsWith('v1:', $backfilledTokens['refresh_token']);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $I->assertSame($backfilledTokens, $this->fetchStoredTokens($I, $integration));

        $I->getEntityManager()->clear();
        $backfilledIntegration = $I->getEntityManager()->find(LarpIntegration::class, $integration->getId());
        $I->assertSame('legacy-access-token', $backfilledIntegration->getAccessToken());
        $I->assertSame('legacy-refresh-token', $backfilledIntegration->getRefreshToken());
    }

    private function createIntegration(string $accessToken, string $refreshToken): LarpIntegration
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        return LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->google()
            ->with(['accessToken' => $accessToken, 'refreshToken' => $refreshToken])
            ->create()
            ->_real();
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function fetchStoredTokens(FunctionalTester $I, LarpIntegration $integration): array
    {
        /** @var array{access_token: string, refresh_token: string} $storedTokens */
        $storedTokens = $I->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT access_token, refresh_token FROM larp_integration WHERE id = :id',
            ['id' => $integration->getId()->toRfc4122()],
        );

        return $storedTokens;
    }
}
