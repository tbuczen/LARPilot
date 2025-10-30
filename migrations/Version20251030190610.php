<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251030190610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE larp ADD discord_server_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp ADD facebook_event_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE larp ADD header_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp DROP discord_server_url');
        $this->addSql('ALTER TABLE larp DROP facebook_event_url');
        $this->addSql('ALTER TABLE larp DROP header_image');
    }
}
