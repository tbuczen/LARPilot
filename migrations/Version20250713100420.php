<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713100420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quest_involved_characters (quest_id UUID NOT NULL, larp_character_id UUID NOT NULL, PRIMARY KEY(quest_id, larp_character_id))');
        $this->addSql('CREATE INDEX IDX_ADD0856C209E9EF4 ON quest_involved_characters (quest_id)');
        $this->addSql('CREATE INDEX IDX_ADD0856CB89790D2 ON quest_involved_characters (larp_character_id)');
        $this->addSql('COMMENT ON COLUMN quest_involved_characters.quest_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN quest_involved_characters.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE quest_involved_factions (quest_id UUID NOT NULL, larp_faction_id UUID NOT NULL, PRIMARY KEY(quest_id, larp_faction_id))');
        $this->addSql('CREATE INDEX IDX_4232C17E209E9EF4 ON quest_involved_factions (quest_id)');
        $this->addSql('CREATE INDEX IDX_4232C17E73AC70CA ON quest_involved_factions (larp_faction_id)');
        $this->addSql('COMMENT ON COLUMN quest_involved_factions.quest_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN quest_involved_factions.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE thread_involved_characters (thread_id UUID NOT NULL, larp_character_id UUID NOT NULL, PRIMARY KEY(thread_id, larp_character_id))');
        $this->addSql('CREATE INDEX IDX_CF6AD68E2904019 ON thread_involved_characters (thread_id)');
        $this->addSql('CREATE INDEX IDX_CF6AD68B89790D2 ON thread_involved_characters (larp_character_id)');
        $this->addSql('COMMENT ON COLUMN thread_involved_characters.thread_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN thread_involved_characters.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE thread_involved_factions (thread_id UUID NOT NULL, larp_faction_id UUID NOT NULL, PRIMARY KEY(thread_id, larp_faction_id))');
        $this->addSql('CREATE INDEX IDX_9B1F3DEAE2904019 ON thread_involved_factions (thread_id)');
        $this->addSql('CREATE INDEX IDX_9B1F3DEA73AC70CA ON thread_involved_factions (larp_faction_id)');
        $this->addSql('COMMENT ON COLUMN thread_involved_factions.thread_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN thread_involved_factions.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE quest_involved_characters ADD CONSTRAINT FK_ADD0856C209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quest_involved_characters ADD CONSTRAINT FK_ADD0856CB89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quest_involved_factions ADD CONSTRAINT FK_4232C17E209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quest_involved_factions ADD CONSTRAINT FK_4232C17E73AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE thread_involved_characters ADD CONSTRAINT FK_CF6AD68E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE thread_involved_characters ADD CONSTRAINT FK_CF6AD68B89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE thread_involved_factions ADD CONSTRAINT FK_9B1F3DEAE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE thread_involved_factions ADD CONSTRAINT FK_9B1F3DEA73AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT FK_ADD0856C209E9EF4');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT FK_ADD0856CB89790D2');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT FK_4232C17E209E9EF4');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT FK_4232C17E73AC70CA');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT FK_CF6AD68E2904019');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT FK_CF6AD68B89790D2');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT FK_9B1F3DEAE2904019');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT FK_9B1F3DEA73AC70CA');
        $this->addSql('DROP TABLE quest_involved_characters');
        $this->addSql('DROP TABLE quest_involved_factions');
        $this->addSql('DROP TABLE thread_involved_characters');
        $this->addSql('DROP TABLE thread_involved_factions');
    }
}
