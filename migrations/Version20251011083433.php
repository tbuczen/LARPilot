<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011083433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Note: All FK/index/PK additions moved to Version20251011230631 after column renames
        $this->addSql('ALTER TABLE larp ADD min_threads_per_character SMALLINT DEFAULT 3 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        // Note: All PK drops moved to Version20251011230631
        $this->addSql('ALTER TABLE larp DROP min_threads_per_character');
    }
}
