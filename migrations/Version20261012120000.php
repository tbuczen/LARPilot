<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public const BACKFILL_LARP_SQL = <<<'SQL'
        UPDATE external_reference
        SET larp_id = story_object.larp_id
        FROM story_object
        WHERE external_reference.story_object_id = story_object.id
          AND external_reference.larp_id IS NULL
        SQL;

    public const DELETE_UNSCOPED_REFERENCES_SQL = 'DELETE FROM external_reference WHERE larp_id IS NULL';

    public const DEDUPLICATE_REFERENCES_SQL = <<<'SQL'
        DELETE FROM external_reference
        USING (
            SELECT id, ROW_NUMBER() OVER (
                PARTITION BY story_object_id, provider, external_id
                ORDER BY updated_at DESC, created_at DESC, id DESC
            ) AS position
            FROM external_reference
            WHERE story_object_id IS NOT NULL
        ) AS ranked_reference
        WHERE external_reference.id = ranked_reference.id
          AND ranked_reference.position > 1
        SQL;

    public function getDescription(): string
    {
        return 'Add external sources and items, scope external references to a LARP and make them unique per story object';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE external_source (id UUID NOT NULL, integration_id UUID NOT NULL, kind VARCHAR(50) NOT NULL, external_id VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, url TEXT DEFAULT NULL, enabled BOOLEAN DEFAULT true NOT NULL, sync_cursor JSONB DEFAULT NULL, config JSONB NOT NULL, last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_sync_status VARCHAR(20) DEFAULT NULL, last_sync_error TEXT DEFAULT NULL, next_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2B09E09A9E82DDEA ON external_source (integration_id)');
        $this->addSql('CREATE INDEX IDX_2B09E09AEDB074EC ON external_source (next_sync_at)');
        $this->addSql('CREATE UNIQUE INDEX uniq_external_source_integration_external_id ON external_source (integration_id, external_id)');
        $this->addSql('COMMENT ON COLUMN external_source.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_source.integration_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_source.last_synced_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN external_source.next_sync_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE external_source ADD CONSTRAINT FK_2B09E09A9E82DDEA FOREIGN KEY (integration_id) REFERENCES larp_integration (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE external_item (id UUID NOT NULL, source_id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, kind VARCHAR(30) NOT NULL, parent_external_id VARCHAR(255) DEFAULT NULL, title VARCHAR(500) DEFAULT NULL, excerpt TEXT DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, url TEXT DEFAULT NULL, external_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_seen_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, availability VARCHAR(20) DEFAULT \'ok\' NOT NULL, raw JSONB DEFAULT NULL, review_status VARCHAR(20) DEFAULT \'unreviewed\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8A044532953C1C61 ON external_item (source_id)');
        $this->addSql('CREATE INDEX IDX_8A044532953C1C61F17622DC ON external_item (source_id, review_status)');
        $this->addSql('CREATE UNIQUE INDEX uniq_external_item_source_external_id ON external_item (source_id, external_id)');
        $this->addSql('COMMENT ON COLUMN external_item.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_item.source_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_item.external_updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN external_item.last_seen_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE external_item ADD CONSTRAINT FK_8A044532953C1C61 FOREIGN KEY (source_id) REFERENCES external_source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE external_reference ADD larp_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE external_reference ADD source_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE external_reference ADD external_item_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE external_reference ADD external_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE external_reference ADD last_seen_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE external_reference ADD excerpt TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN external_reference.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_reference.source_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_reference.external_item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN external_reference.external_updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN external_reference.last_seen_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(self::BACKFILL_LARP_SQL);
        $this->addSql(self::DELETE_UNSCOPED_REFERENCES_SQL);
        $this->addSql(self::DEDUPLICATE_REFERENCES_SQL);

        $this->addSql('ALTER TABLE external_reference ALTER larp_id SET NOT NULL');
        $this->addSql('ALTER TABLE external_reference ADD CONSTRAINT FK_8AF8E60763FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_reference ADD CONSTRAINT FK_8AF8E607953C1C61 FOREIGN KEY (source_id) REFERENCES external_source (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_reference ADD CONSTRAINT FK_8AF8E607CBCD8B1D FOREIGN KEY (external_item_id) REFERENCES external_item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8AF8E60763FF2A01 ON external_reference (larp_id)');
        $this->addSql('CREATE INDEX IDX_8AF8E607953C1C61 ON external_reference (source_id)');
        $this->addSql('CREATE INDEX IDX_8AF8E607CBCD8B1D ON external_reference (external_item_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_external_reference_story_object_provider_external_id ON external_reference (story_object_id, provider, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_external_reference_story_object_provider_external_id');
        $this->addSql('ALTER TABLE external_reference DROP CONSTRAINT FK_8AF8E60763FF2A01');
        $this->addSql('ALTER TABLE external_reference DROP CONSTRAINT FK_8AF8E607953C1C61');
        $this->addSql('ALTER TABLE external_reference DROP CONSTRAINT FK_8AF8E607CBCD8B1D');
        $this->addSql('DROP INDEX IDX_8AF8E60763FF2A01');
        $this->addSql('DROP INDEX IDX_8AF8E607953C1C61');
        $this->addSql('DROP INDEX IDX_8AF8E607CBCD8B1D');
        $this->addSql('ALTER TABLE external_reference DROP larp_id');
        $this->addSql('ALTER TABLE external_reference DROP source_id');
        $this->addSql('ALTER TABLE external_reference DROP external_item_id');
        $this->addSql('ALTER TABLE external_reference DROP external_updated_at');
        $this->addSql('ALTER TABLE external_reference DROP last_seen_at');
        $this->addSql('ALTER TABLE external_reference DROP excerpt');
        $this->addSql('ALTER TABLE external_item DROP CONSTRAINT FK_8A044532953C1C61');
        $this->addSql('ALTER TABLE external_source DROP CONSTRAINT FK_2B09E09A9E82DDEA');
        $this->addSql('DROP TABLE external_item');
        $this->addSql('DROP TABLE external_source');
    }
}
