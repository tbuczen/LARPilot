<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714092007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location (id UUID NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, facebook VARCHAR(255) DEFAULT NULL, instagram VARCHAR(255) DEFAULT NULL, twitter VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(255) DEFAULT NULL, images JSON DEFAULT NULL, facilities TEXT DEFAULT NULL, accessibility TEXT DEFAULT NULL, parking_info TEXT DEFAULT NULL, public_transport TEXT DEFAULT NULL, capacity INT DEFAULT NULL, is_active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CB989D9B62 ON location (slug)');
        $this->addSql('COMMENT ON COLUMN location.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp ADD location_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE larp ADD setting VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp ADD character_system VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp RENAME COLUMN name TO title');
        $this->addSql('ALTER TABLE larp DROP location');
        $this->addSql('COMMENT ON COLUMN larp.location_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp ADD CONSTRAINT FK_54AD5CF864D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_54AD5CF864D218E ON larp (location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp DROP CONSTRAINT FK_54AD5CF864D218E');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP INDEX IDX_54AD5CF864D218E');
        $this->addSql('ALTER TABLE larp ADD location VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE larp DROP location_id');
        $this->addSql('ALTER TABLE larp DROP setting');
        $this->addSql('ALTER TABLE larp DROP type');
        $this->addSql('ALTER TABLE larp DROP character_system');
        $this->addSql('ALTER TABLE larp RENAME COLUMN title TO name');
    }
}
