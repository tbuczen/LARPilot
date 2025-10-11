<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011000059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE larp_character_larp_faction RENAME TO character_faction');
        $this->addSql('ALTER TABLE larp_character_quest RENAME TO character_quest');
        $this->addSql('ALTER TABLE larp_character_tags RENAME TO character_tags');
        $this->addSql('ALTER TABLE larp_character_thread RENAME TO character_thread');
        $this->addSql('ALTER TABLE larp_faction_quest RENAME TO faction_quest');
        $this->addSql('ALTER TABLE larp_faction_thread RENAME TO faction_thread');
        $this->addSql('ALTER TABLE event_larp_character RENAME TO event_character');
        $this->addSql('ALTER TABLE event_larp_faction RENAME TO event_faction');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
