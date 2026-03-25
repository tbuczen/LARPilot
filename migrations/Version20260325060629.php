<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325060629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_application_turn (id UUID NOT NULL, larp_id UUID NOT NULL, round_number SMALLINT NOT NULL, price NUMERIC(10, 2) DEFAULT NULL, opens_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closes_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2DB5A17D63FF2A01 ON larp_application_turn (larp_id)');
        $this->addSql('COMMENT ON COLUMN larp_application_turn.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application_turn.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_application_turn ADD CONSTRAINT FK_2DB5A17D63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_application_turn DROP CONSTRAINT FK_2DB5A17D63FF2A01');
        $this->addSql('DROP TABLE larp_application_turn');
    }
}
