<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250328195628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shared_file ADD metadata JSON NOT NULL');
        $this->addSql('ALTER TABLE shared_file ADD permission_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE shared_file ADD mime_type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE shared_file DROP metadata');
        $this->addSql('ALTER TABLE shared_file DROP permission_type');
        $this->addSql('ALTER TABLE shared_file DROP mime_type');
    }
}
