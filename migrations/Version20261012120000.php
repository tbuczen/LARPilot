<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    private const RANKED_INTEGRATIONS = 'WITH ranked AS (SELECT id, FIRST_VALUE(id) OVER (PARTITION BY larp_id, provider ORDER BY created_at DESC, updated_at DESC, id DESC) AS kept_id FROM larp_integration)';

    public function getDescription(): string
    {
        return 'LarpIntegration connection model: connection type, status, nullable tokens, external account, config and unique (larp_id, provider)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(self::RANKED_INTEGRATIONS . ' UPDATE shared_file SET integration_id = ranked.kept_id FROM ranked WHERE shared_file.integration_id = ranked.id AND ranked.id <> ranked.kept_id');
        $this->addSql(self::RANKED_INTEGRATIONS . ' DELETE FROM larp_integration USING ranked WHERE larp_integration.id = ranked.id AND ranked.id <> ranked.kept_id');

        $this->addSql('ALTER TABLE larp_integration ADD connection_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_integration ADD status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_integration ADD external_account_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_integration ADD config JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_integration ADD last_error TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_integration ADD last_checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE larp_integration SET connection_type = 'user_oauth', status = 'active'");
        $this->addSql("UPDATE larp_integration SET external_account_id = owner WHERE provider = 'integration_google_drive'");
        $this->addSql('ALTER TABLE larp_integration ALTER connection_type SET NOT NULL');
        $this->addSql('ALTER TABLE larp_integration ALTER status SET NOT NULL');
        $this->addSql('ALTER TABLE larp_integration ALTER access_token DROP NOT NULL');
        $this->addSql('ALTER TABLE larp_integration ALTER expires_at DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_larp_integration_larp_provider ON larp_integration (larp_id, provider)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_larp_integration_larp_provider');
        $this->addSql("UPDATE larp_integration SET access_token = '' WHERE access_token IS NULL");
        $this->addSql('UPDATE larp_integration SET expires_at = created_at WHERE expires_at IS NULL');
        $this->addSql('ALTER TABLE larp_integration ALTER access_token SET NOT NULL');
        $this->addSql('ALTER TABLE larp_integration ALTER expires_at SET NOT NULL');
        $this->addSql('ALTER TABLE larp_integration DROP connection_type');
        $this->addSql('ALTER TABLE larp_integration DROP status');
        $this->addSql('ALTER TABLE larp_integration DROP external_account_id');
        $this->addSql('ALTER TABLE larp_integration DROP config');
        $this->addSql('ALTER TABLE larp_integration DROP last_error');
        $this->addSql('ALTER TABLE larp_integration DROP last_checked_at');
    }
}
