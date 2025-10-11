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
        $this->addSql('ALTER TABLE character_tags ADD CONSTRAINT FK_784080BE1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_784080BE1136BE75 ON character_tags (character_id)');
        $this->addSql('ALTER TABLE larp ADD min_threads_per_character SMALLINT DEFAULT 3 NOT NULL');
        $this->addSql('ALTER TABLE quest_involved_characters ADD PRIMARY KEY (quest_id, character_id)');
        $this->addSql('ALTER TABLE quest_involved_factions ADD PRIMARY KEY (quest_id, faction_id)');
        $this->addSql('ALTER TABLE thread_involved_characters ADD PRIMARY KEY (thread_id, character_id)');
        $this->addSql('ALTER TABLE thread_involved_factions ADD PRIMARY KEY (thread_id, faction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT quest_involved_factions_pkey');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT thread_involved_factions_pkey');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT thread_involved_characters_pkey');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT quest_involved_characters_pkey');
        $this->addSql('ALTER TABLE character_tags DROP CONSTRAINT FK_784080BE1136BE75');
        $this->addSql('DROP INDEX IDX_784080BE1136BE75');
        $this->addSql('ALTER TABLE larp DROP min_threads_per_character');
    }
}
