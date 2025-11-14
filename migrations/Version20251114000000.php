<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Gallery domain tables
 */
final class Version20251114000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create gallery table for photo gallery management';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gallery (
            id UUID NOT NULL,
            larp_id UUID NOT NULL,
            photographer_id UUID NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            external_album_url VARCHAR(500) DEFAULT NULL,
            zip_download_url VARCHAR(500) DEFAULT NULL,
            zip_file VARCHAR(255) DEFAULT NULL,
            visibility VARCHAR(255) DEFAULT \'participants_only\' NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_472B783A5A6D2C8F ON gallery (larp_id)');
        $this->addSql('CREATE INDEX IDX_472B783ABF03B4B0 ON gallery (photographer_id)');
        $this->addSql('COMMENT ON COLUMN gallery.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN gallery.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN gallery.photographer_id IS \'(DC2Type:uuid)\'');

        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783A5A6D2C8F FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783ABF03B4B0 FOREIGN KEY (photographer_id) REFERENCES larp_participant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery DROP CONSTRAINT FK_472B783A5A6D2C8F');
        $this->addSql('ALTER TABLE gallery DROP CONSTRAINT FK_472B783ABF03B4B0');
        $this->addSql('DROP TABLE gallery');
    }
}
