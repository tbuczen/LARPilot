<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011230631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE character_quest DROP CONSTRAINT fk_cb35ce6b209e9ef4');
        $this->addSql('ALTER TABLE character_quest DROP CONSTRAINT fk_cb35ce6bb89790d2');
        $this->addSql('ALTER TABLE faction_thread DROP CONSTRAINT fk_6450087273ac70ca');
        $this->addSql('ALTER TABLE faction_thread DROP CONSTRAINT fk_64500872e2904019');
        $this->addSql('ALTER TABLE faction_quest DROP CONSTRAINT fk_367526a0209e9ef4');
        $this->addSql('ALTER TABLE faction_quest DROP CONSTRAINT fk_367526a073ac70ca');
        $this->addSql('ALTER TABLE character_thread DROP CONSTRAINT fk_681b53a2b89790d2');
        $this->addSql('ALTER TABLE character_thread DROP CONSTRAINT fk_681b53a2e2904019');
        $this->addSql('DROP TABLE character_quest');
        $this->addSql('DROP TABLE faction_thread');
        $this->addSql('DROP TABLE item_story_object');
        $this->addSql('DROP TABLE faction_quest');
        $this->addSql('DROP TABLE character_thread');
        $this->addSql('ALTER INDEX uniq_afc950dfbce3fc38 RENAME TO UNIQ_937AB034BCE3FC38');
        $this->addSql('ALTER INDEX uniq_afc950dff6aa89a4 RENAME TO UNIQ_937AB034F6AA89A4');
        $this->addSql('ALTER INDEX idx_afc950dfe27ddcc7 RENAME TO IDX_937AB034E27DDCC7');
        $this->addSql('ALTER INDEX idx_afc950dfa08cbe59 RENAME TO IDX_937AB034A08CBE59');
        $this->addSql('ALTER INDEX idx_afc950dfd7acf689 RENAME TO IDX_937AB034D7ACF689');
        $this->addSql('ALTER TABLE character_tags DROP CONSTRAINT fk_45d00efeb89790d2');
        $this->addSql('DROP INDEX idx_45d00efeb89790d2');
        $this->addSql('ALTER TABLE character_tags RENAME COLUMN larp_character_id TO character_id');
//        $this->addSql('ALTER TABLE character_tags ADD CONSTRAINT FK_784080BE1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
//        $this->addSql('CREATE INDEX IDX_784080BE1136BE75 ON character_tags (character_id)');
//        $this->addSql('ALTER TABLE character_tags ADD PRIMARY KEY (character_id, tag_id)');
        $this->addSql('ALTER INDEX idx_45d00efebad26311 RENAME TO IDX_784080BEBAD26311');
        $this->addSql('ALTER TABLE character_faction DROP CONSTRAINT fk_3da131b573ac70ca');
        $this->addSql('ALTER TABLE character_faction DROP CONSTRAINT fk_3da131b5b89790d2');
        $this->addSql('DROP INDEX idx_3da131b573ac70ca');
        $this->addSql('DROP INDEX idx_3da131b5b89790d2');
        $this->addSql('ALTER TABLE character_faction RENAME COLUMN larp_character_id to character_id');
        $this->addSql('ALTER TABLE character_faction RENAME COLUMN larp_faction_id to faction_id');
        $this->addSql('COMMENT ON COLUMN character_faction.character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN character_faction.faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE character_faction ADD CONSTRAINT FK_EAA10BAD1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_faction ADD CONSTRAINT FK_EAA10BAD4448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_EAA10BAD1136BE75 ON character_faction (character_id)');
        $this->addSql('CREATE INDEX IDX_EAA10BAD4448F8DA ON character_faction (faction_id)');
        $this->addSql('ALTER INDEX idx_3577bfc61136be75 RENAME TO IDX_8E731861136BE75');
        $this->addSql('ALTER INDEX idx_3577bfc6126f525e RENAME TO IDX_8E73186126F525E');
        $this->addSql('ALTER INDEX idx_d61fd20b1136be75 RENAME TO IDX_A0FE03151136BE75');
        $this->addSql('ALTER INDEX idx_d61fd20b5585c142 RENAME TO IDX_A0FE03155585C142');
        $this->addSql('ALTER TABLE event_character DROP CONSTRAINT fk_708de6acb89790d2');
        $this->addSql('DROP INDEX idx_708de6acb89790d2');
        $this->addSql('ALTER TABLE event_character RENAME COLUMN larp_character_id TO character_id');
        $this->addSql('ALTER TABLE event_character ADD CONSTRAINT FK_15EF8E091136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_15EF8E091136BE75 ON event_character (character_id)');
        $this->addSql('ALTER INDEX idx_708de6ac71f7e88b RENAME TO IDX_15EF8E0971F7E88B');
        $this->addSql('ALTER TABLE event_faction DROP CONSTRAINT fk_bab5871973ac70ca');
        $this->addSql('DROP INDEX idx_bab5871973ac70ca');
        $this->addSql('ALTER TABLE event_faction RENAME COLUMN larp_faction_id TO faction_id');
        $this->addSql('ALTER TABLE event_faction ADD CONSTRAINT FK_653223F4448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_653223F4448F8DA ON event_faction (faction_id)');
        $this->addSql('ALTER INDEX idx_bab5871971f7e88b RENAME TO IDX_653223F71F7E88B');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT fk_add0856cb89790d2');
        $this->addSql('DROP INDEX idx_add0856cb89790d2');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT quest_involved_characters_pkey');
        $this->addSql('ALTER TABLE quest_involved_characters RENAME COLUMN larp_character_id TO character_id');
        $this->addSql('ALTER TABLE quest_involved_characters ADD CONSTRAINT FK_ADD0856C1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_ADD0856C1136BE75 ON quest_involved_characters (character_id)');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT fk_4232c17e73ac70ca');
        $this->addSql('DROP INDEX idx_4232c17e73ac70ca');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT quest_involved_factions_pkey');
        $this->addSql('ALTER TABLE quest_involved_factions RENAME COLUMN larp_faction_id TO faction_id');
        $this->addSql('ALTER TABLE quest_involved_factions ADD CONSTRAINT FK_4232C17E4448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_4232C17E4448F8DA ON quest_involved_factions (faction_id)');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT fk_cf6ad68b89790d2');
        $this->addSql('DROP INDEX idx_cf6ad68b89790d2');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT thread_involved_characters_pkey');
        $this->addSql('ALTER TABLE thread_involved_characters RENAME COLUMN larp_character_id TO character_id');
        $this->addSql('ALTER TABLE thread_involved_characters ADD CONSTRAINT FK_CF6AD681136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CF6AD681136BE75 ON thread_involved_characters (character_id)');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT fk_9b1f3dea73ac70ca');
        $this->addSql('DROP INDEX idx_9b1f3dea73ac70ca');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT thread_involved_factions_pkey');
        $this->addSql('ALTER TABLE thread_involved_factions RENAME COLUMN larp_faction_id TO faction_id');
        $this->addSql('ALTER TABLE thread_involved_factions ADD CONSTRAINT FK_9B1F3DEA4448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9B1F3DEA4448F8DA ON thread_involved_factions (faction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE character_quest (larp_character_id UUID NOT NULL, quest_id UUID NOT NULL, PRIMARY KEY(larp_character_id, quest_id))');
        $this->addSql('CREATE INDEX idx_cb35ce6b209e9ef4 ON character_quest (quest_id)');
        $this->addSql('CREATE INDEX idx_cb35ce6bb89790d2 ON character_quest (larp_character_id)');
        $this->addSql('COMMENT ON COLUMN character_quest.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN character_quest.quest_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE faction_thread (larp_faction_id UUID NOT NULL, thread_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, thread_id))');
        $this->addSql('CREATE INDEX idx_6450087273ac70ca ON faction_thread (larp_faction_id)');
        $this->addSql('CREATE INDEX idx_64500872e2904019 ON faction_thread (thread_id)');
        $this->addSql('COMMENT ON COLUMN faction_thread.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN faction_thread.thread_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE item_story_object (item_id UUID NOT NULL, story_object_id UUID NOT NULL, PRIMARY KEY(item_id, story_object_id))');
        $this->addSql('CREATE INDEX idx_28f4e4df126f525e ON item_story_object (item_id)');
        $this->addSql('CREATE INDEX idx_28f4e4dfda976c5a ON item_story_object (story_object_id)');
        $this->addSql('COMMENT ON COLUMN item_story_object.item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN item_story_object.story_object_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE faction_quest (larp_faction_id UUID NOT NULL, quest_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, quest_id))');
        $this->addSql('CREATE INDEX idx_367526a0209e9ef4 ON faction_quest (quest_id)');
        $this->addSql('CREATE INDEX idx_367526a073ac70ca ON faction_quest (larp_faction_id)');
        $this->addSql('COMMENT ON COLUMN faction_quest.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN faction_quest.quest_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE character_thread (larp_character_id UUID NOT NULL, thread_id UUID NOT NULL, PRIMARY KEY(larp_character_id, thread_id))');
        $this->addSql('CREATE INDEX idx_681b53a2b89790d2 ON character_thread (larp_character_id)');
        $this->addSql('CREATE INDEX idx_681b53a2e2904019 ON character_thread (thread_id)');
        $this->addSql('COMMENT ON COLUMN character_thread.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN character_thread.thread_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE character_quest ADD CONSTRAINT fk_cb35ce6b209e9ef4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_quest ADD CONSTRAINT fk_cb35ce6bb89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faction_thread ADD CONSTRAINT fk_6450087273ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faction_thread ADD CONSTRAINT fk_64500872e2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faction_quest ADD CONSTRAINT fk_367526a0209e9ef4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faction_quest ADD CONSTRAINT fk_367526a073ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_thread ADD CONSTRAINT fk_681b53a2b89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_thread ADD CONSTRAINT fk_681b53a2e2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_character DROP CONSTRAINT FK_15EF8E091136BE75');
        $this->addSql('DROP INDEX IDX_15EF8E091136BE75');
        $this->addSql('DROP INDEX event_larp_character_pkey');
        $this->addSql('ALTER TABLE event_character RENAME COLUMN character_id TO larp_character_id');
        $this->addSql('ALTER TABLE event_character ADD CONSTRAINT fk_708de6acb89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_708de6acb89790d2 ON event_character (larp_character_id)');
        $this->addSql('ALTER TABLE event_character ADD PRIMARY KEY (event_id, larp_character_id)');
        $this->addSql('ALTER INDEX idx_15ef8e0971f7e88b RENAME TO idx_708de6ac71f7e88b');
        $this->addSql('ALTER TABLE character_tags DROP CONSTRAINT FK_784080BE1136BE75');
        $this->addSql('DROP INDEX IDX_784080BE1136BE75');
        $this->addSql('DROP INDEX larp_character_tags_pkey');
        $this->addSql('ALTER TABLE character_tags RENAME COLUMN character_id TO larp_character_id');
        $this->addSql('ALTER TABLE character_tags ADD CONSTRAINT fk_45d00efeb89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_45d00efeb89790d2 ON character_tags (larp_character_id)');
        $this->addSql('ALTER TABLE character_tags ADD PRIMARY KEY (larp_character_id, tag_id)');
        $this->addSql('ALTER INDEX idx_784080bebad26311 RENAME TO idx_45d00efebad26311');
        $this->addSql('ALTER INDEX idx_a0fe03151136be75 RENAME TO idx_d61fd20b1136be75');
        $this->addSql('ALTER INDEX idx_a0fe03155585c142 RENAME TO idx_d61fd20b5585c142');
        $this->addSql('ALTER INDEX idx_8e731861136be75 RENAME TO idx_3577bfc61136be75');
        $this->addSql('ALTER INDEX idx_8e73186126f525e RENAME TO idx_3577bfc6126f525e');
        $this->addSql('ALTER TABLE character_faction DROP CONSTRAINT FK_EAA10BAD1136BE75');
        $this->addSql('ALTER TABLE character_faction DROP CONSTRAINT FK_EAA10BAD4448F8DA');
        $this->addSql('DROP INDEX IDX_EAA10BAD1136BE75');
        $this->addSql('DROP INDEX IDX_EAA10BAD4448F8DA');
        $this->addSql('DROP INDEX larp_character_larp_faction_pkey');
        $this->addSql('ALTER TABLE character_faction ADD larp_character_id UUID NOT NULL');
        $this->addSql('ALTER TABLE character_faction ADD larp_faction_id UUID NOT NULL');
        $this->addSql('ALTER TABLE character_faction DROP character_id');
        $this->addSql('ALTER TABLE character_faction DROP faction_id');
        $this->addSql('COMMENT ON COLUMN character_faction.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN character_faction.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE character_faction ADD CONSTRAINT fk_3da131b573ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_faction ADD CONSTRAINT fk_3da131b5b89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3da131b573ac70ca ON character_faction (larp_faction_id)');
        $this->addSql('CREATE INDEX idx_3da131b5b89790d2 ON character_faction (larp_character_id)');
        $this->addSql('ALTER TABLE character_faction ADD PRIMARY KEY (larp_character_id, larp_faction_id)');
        $this->addSql('ALTER TABLE thread_involved_factions DROP CONSTRAINT FK_9B1F3DEA4448F8DA');
        $this->addSql('DROP INDEX IDX_9B1F3DEA4448F8DA');
        $this->addSql('DROP INDEX thread_involved_factions_pkey');
        $this->addSql('ALTER TABLE thread_involved_factions RENAME COLUMN faction_id TO larp_faction_id');
        $this->addSql('ALTER TABLE thread_involved_factions ADD CONSTRAINT fk_9b1f3dea73ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_9b1f3dea73ac70ca ON thread_involved_factions (larp_faction_id)');
        $this->addSql('ALTER TABLE thread_involved_factions ADD PRIMARY KEY (thread_id, larp_faction_id)');
        $this->addSql('ALTER INDEX idx_937ab034a08cbe59 RENAME TO idx_afc950dfa08cbe59');
        $this->addSql('ALTER INDEX idx_937ab034d7acf689 RENAME TO idx_afc950dfd7acf689');
        $this->addSql('ALTER INDEX idx_937ab034e27ddcc7 RENAME TO idx_afc950dfe27ddcc7');
        $this->addSql('ALTER INDEX uniq_937ab034bce3fc38 RENAME TO uniq_afc950dfbce3fc38');
        $this->addSql('ALTER INDEX uniq_937ab034f6aa89a4 RENAME TO uniq_afc950dff6aa89a4');
        $this->addSql('ALTER TABLE thread_involved_characters DROP CONSTRAINT FK_CF6AD681136BE75');
        $this->addSql('DROP INDEX IDX_CF6AD681136BE75');
        $this->addSql('DROP INDEX thread_involved_characters_pkey');
        $this->addSql('ALTER TABLE thread_involved_characters RENAME COLUMN character_id TO larp_character_id');
        $this->addSql('ALTER TABLE thread_involved_characters ADD CONSTRAINT fk_cf6ad68b89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_cf6ad68b89790d2 ON thread_involved_characters (larp_character_id)');
        $this->addSql('ALTER TABLE thread_involved_characters ADD PRIMARY KEY (thread_id, larp_character_id)');
        $this->addSql('ALTER TABLE quest_involved_factions DROP CONSTRAINT FK_4232C17E4448F8DA');
        $this->addSql('DROP INDEX IDX_4232C17E4448F8DA');
        $this->addSql('DROP INDEX quest_involved_factions_pkey');
        $this->addSql('ALTER TABLE quest_involved_factions RENAME COLUMN faction_id TO larp_faction_id');
        $this->addSql('ALTER TABLE quest_involved_factions ADD CONSTRAINT fk_4232c17e73ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_4232c17e73ac70ca ON quest_involved_factions (larp_faction_id)');
        $this->addSql('ALTER TABLE quest_involved_factions ADD PRIMARY KEY (quest_id, larp_faction_id)');
        $this->addSql('ALTER TABLE quest_involved_characters DROP CONSTRAINT FK_ADD0856C1136BE75');
        $this->addSql('DROP INDEX IDX_ADD0856C1136BE75');
        $this->addSql('DROP INDEX quest_involved_characters_pkey');
        $this->addSql('ALTER TABLE quest_involved_characters RENAME COLUMN character_id TO larp_character_id');
        $this->addSql('ALTER TABLE quest_involved_characters ADD CONSTRAINT fk_add0856cb89790d2 FOREIGN KEY (larp_character_id) REFERENCES "character" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_add0856cb89790d2 ON quest_involved_characters (larp_character_id)');
        $this->addSql('ALTER TABLE quest_involved_characters ADD PRIMARY KEY (quest_id, larp_character_id)');
        $this->addSql('ALTER TABLE event_faction DROP CONSTRAINT FK_653223F4448F8DA');
        $this->addSql('DROP INDEX IDX_653223F4448F8DA');
        $this->addSql('DROP INDEX event_larp_faction_pkey');
        $this->addSql('ALTER TABLE event_faction RENAME COLUMN faction_id TO larp_faction_id');
        $this->addSql('ALTER TABLE event_faction ADD CONSTRAINT fk_bab5871973ac70ca FOREIGN KEY (larp_faction_id) REFERENCES faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_bab5871973ac70ca ON event_faction (larp_faction_id)');
        $this->addSql('ALTER TABLE event_faction ADD PRIMARY KEY (event_id, larp_faction_id)');
        $this->addSql('ALTER INDEX idx_653223f71f7e88b RENAME TO idx_bab5871971f7e88b');
    }
}
