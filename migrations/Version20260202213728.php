<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202213728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add LoreDocument entity for general world-building content (extends StoryObject)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lore_document (id UUID NOT NULL, category VARCHAR(30) NOT NULL, priority INT DEFAULT 50 NOT NULL, always_include_in_context BOOLEAN DEFAULT false NOT NULL, active BOOLEAN DEFAULT true NOT NULL, summary TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN lore_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE lore_document_tags (lore_document_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(lore_document_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_B6D256829AE48167 ON lore_document_tags (lore_document_id)');
        $this->addSql('CREATE INDEX IDX_B6D25682BAD26311 ON lore_document_tags (tag_id)');
        $this->addSql('COMMENT ON COLUMN lore_document_tags.lore_document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN lore_document_tags.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE lore_document ADD CONSTRAINT FK_40DB29E0BF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lore_document_tags ADD CONSTRAINT FK_B6D256829AE48167 FOREIGN KEY (lore_document_id) REFERENCES lore_document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lore_document_tags ADD CONSTRAINT FK_B6D25682BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE lore_document DROP CONSTRAINT FK_40DB29E0BF396750');
        $this->addSql('ALTER TABLE lore_document_tags DROP CONSTRAINT FK_B6D256829AE48167');
        $this->addSql('ALTER TABLE lore_document_tags DROP CONSTRAINT FK_B6D25682BAD26311');
        $this->addSql('DROP TABLE lore_document');
        $this->addSql('DROP TABLE lore_document_tags');
    }
}
