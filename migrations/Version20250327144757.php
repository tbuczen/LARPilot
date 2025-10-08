<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327144757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shared_file (id UUID NOT NULL, integration_id UUID NOT NULL, file_id VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_36695D889E82DDEA ON shared_file (integration_id)');
        $this->addSql('COMMENT ON COLUMN shared_file.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN shared_file.integration_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE shared_file ADD CONSTRAINT FK_36695D889E82DDEA FOREIGN KEY (integration_id) REFERENCES larp_integration (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_integration ADD owner VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE shared_file DROP CONSTRAINT FK_36695D889E82DDEA');
        $this->addSql('DROP TABLE shared_file');
        $this->addSql('ALTER TABLE larp_integration DROP owner');
    }
}
