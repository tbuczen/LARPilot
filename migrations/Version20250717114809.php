<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250717114809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD created_by_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN location.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5E9E89CBB03A8386 ON location (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CBB03A8386');
        $this->addSql('DROP INDEX IDX_5E9E89CBB03A8386');
        $this->addSql('ALTER TABLE location DROP created_by_id');
    }
}
