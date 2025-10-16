<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015120131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT fk_8b94a2bb1136be75');
        $this->addSql('DROP INDEX idx_8b94a2bb1136be75');
        $this->addSql('ALTER TABLE planning_resource ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_resource DROP character_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE planning_resource ADD character_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_resource DROP deleted_at');
        $this->addSql('COMMENT ON COLUMN planning_resource.character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT fk_8b94a2bb1136be75 FOREIGN KEY (character_id) REFERENCES "character" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8b94a2bb1136be75 ON planning_resource (character_id)');
    }
}
