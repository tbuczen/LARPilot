<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303195842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_integration (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, provider VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, scopes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E069997763FF2A01 ON larp_integration (larp_id)');
        $this->addSql('CREATE INDEX IDX_E0699977B03A8386 ON larp_integration (created_by_id)');
        $this->addSql('COMMENT ON COLUMN larp_integration.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_integration.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_integration.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_integration ADD CONSTRAINT FK_E069997763FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_integration ADD CONSTRAINT FK_E0699977B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp ALTER created_by_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_integration DROP CONSTRAINT FK_E069997763FF2A01');
        $this->addSql('ALTER TABLE larp_integration DROP CONSTRAINT FK_E0699977B03A8386');
        $this->addSql('DROP TABLE larp_integration');
        $this->addSql('ALTER TABLE larp ALTER created_by_id DROP NOT NULL');
    }
}
