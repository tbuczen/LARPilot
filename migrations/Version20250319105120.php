<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250319105120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_character_larp_faction (larp_character_id UUID NOT NULL, larp_faction_id UUID NOT NULL, PRIMARY KEY(larp_character_id, larp_faction_id))');
        $this->addSql('CREATE INDEX IDX_3DA131B5B89790D2 ON larp_character_larp_faction (larp_character_id)');
        $this->addSql('CREATE INDEX IDX_3DA131B573AC70CA ON larp_character_larp_faction (larp_faction_id)');
        $this->addSql('COMMENT ON COLUMN larp_character_larp_faction.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_character_larp_faction.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE object_field_mapping (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, file_type VARCHAR(255) NOT NULL, external_file_id VARCHAR(255) DEFAULT NULL, mapping_configuration JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_74F3875863FF2A01 ON object_field_mapping (larp_id)');
        $this->addSql('CREATE INDEX IDX_74F38758B03A8386 ON object_field_mapping (created_by_id)');
        $this->addSql('COMMENT ON COLUMN object_field_mapping.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN object_field_mapping.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN object_field_mapping.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_character_larp_faction ADD CONSTRAINT FK_3DA131B5B89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_character_larp_faction ADD CONSTRAINT FK_3DA131B573AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE object_field_mapping ADD CONSTRAINT FK_74F3875863FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE object_field_mapping ADD CONSTRAINT FK_74F38758B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_participant ADD larp_character_id UUID');
        $this->addSql('COMMENT ON COLUMN larp_participant.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFDB89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CA1F9FFDB89790D2 ON larp_participant (larp_character_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_character_larp_faction DROP CONSTRAINT FK_3DA131B5B89790D2');
        $this->addSql('ALTER TABLE larp_character_larp_faction DROP CONSTRAINT FK_3DA131B573AC70CA');
        $this->addSql('ALTER TABLE object_field_mapping DROP CONSTRAINT FK_74F3875863FF2A01');
        $this->addSql('ALTER TABLE object_field_mapping DROP CONSTRAINT FK_74F38758B03A8386');
        $this->addSql('DROP TABLE larp_character_larp_faction');
        $this->addSql('DROP TABLE object_field_mapping');
        $this->addSql('ALTER TABLE larp_participant DROP CONSTRAINT FK_CA1F9FFDB89790D2');
        $this->addSql('DROP INDEX IDX_CA1F9FFDB89790D2');
        $this->addSql('ALTER TABLE larp_participant DROP larp_character_id');
    }
}
