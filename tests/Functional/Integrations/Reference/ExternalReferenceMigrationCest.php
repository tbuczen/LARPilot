<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Reference;

use Doctrine\DBAL\Connection;
use DoctrineMigrations\Version20261012120000;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class ExternalReferenceMigrationCest
{
    public function _before(FunctionalTester $I): void
    {
        require_once codecept_root_dir() . 'migrations/Version20261012120000.php';
    }

    public function backfillsLarpFromStoryObject(FunctionalTester $I): void
    {
        $connection = $this->grabConnection($I);
        $character = CharacterFactory::new()->create();
        $creator = UserFactory::createApprovedUser();
        $connection->executeStatement('ALTER TABLE external_reference ALTER larp_id DROP NOT NULL');
        $referenceId = $this->insertLegacyReference($connection, $character->getId(), $creator->getId(), 'doc-1', '2026-01-01 10:00:00');
        $orphanReferenceId = $this->insertLegacyReference($connection, null, $creator->getId(), 'doc-2', '2026-01-01 10:00:00');

        $connection->executeStatement(Version20261012120000::BACKFILL_LARP_SQL);
        $connection->executeStatement(Version20261012120000::DELETE_UNSCOPED_REFERENCES_SQL);

        $I->assertSame(
            $character->getLarp()->getId()->toRfc4122(),
            $connection->fetchOne('SELECT larp_id FROM external_reference WHERE id = ?', [$referenceId]),
        );
        $I->assertFalse($connection->fetchOne('SELECT id FROM external_reference WHERE id = ?', [$orphanReferenceId]));
    }

    public function deduplicationKeepsNewestReference(FunctionalTester $I): void
    {
        $connection = $this->grabConnection($I);
        $character = CharacterFactory::new()->create();
        $otherCharacter = CharacterFactory::new()->create();
        $creator = UserFactory::createApprovedUser();
        $connection->executeStatement('DROP INDEX uniq_external_reference_story_object_provider_external_id');
        $connection->executeStatement('ALTER TABLE external_reference ALTER larp_id DROP NOT NULL');
        $olderReferenceId = $this->insertLegacyReference($connection, $character->getId(), $creator->getId(), 'doc-1', '2026-01-01 10:00:00');
        $newestReferenceId = $this->insertLegacyReference($connection, $character->getId(), $creator->getId(), 'doc-1', '2026-03-01 10:00:00');
        $otherReferenceId = $this->insertLegacyReference($connection, $otherCharacter->getId(), $creator->getId(), 'doc-1', '2026-01-01 10:00:00');

        $connection->executeStatement(Version20261012120000::DEDUPLICATE_REFERENCES_SQL);

        $remainingReferenceIds = $connection->fetchFirstColumn('SELECT id FROM external_reference WHERE external_id = ?', ['doc-1']);
        $I->assertEqualsCanonicalizing([$newestReferenceId, $otherReferenceId], $remainingReferenceIds);
        $I->assertNotContains($olderReferenceId, $remainingReferenceIds);
    }

    public function uniqueConstraintExistsOnStoryObjectProviderAndExternalId(FunctionalTester $I): void
    {
        $indexDefinition = $this->grabConnection($I)->fetchOne(
            'SELECT indexdef FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            ['external_reference', 'uniq_external_reference_story_object_provider_external_id'],
        );

        $I->assertIsString($indexDefinition);
        $I->assertStringContainsString('UNIQUE', $indexDefinition);
        $I->assertStringContainsString('(story_object_id, provider, external_id)', $indexDefinition);
    }

    private function insertLegacyReference(Connection $connection, ?Uuid $storyObjectId, Uuid $creatorId, string $externalId, string $updatedAt): string
    {
        $referenceId = Uuid::v4()->toRfc4122();
        $connection->insert('external_reference', [
            'id' => $referenceId,
            'story_object_id' => $storyObjectId?->toRfc4122(),
            'created_by_id' => $creatorId->toRfc4122(),
            'provider' => 'integration_google_drive',
            'external_id' => $externalId,
            'reference_type' => 'document',
            'role' => 'mention',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        return $referenceId;
    }

    private function grabConnection(FunctionalTester $I): Connection
    {
        return $I->grabService('doctrine.dbal.default_connection');
    }
}
