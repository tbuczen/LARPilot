<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426165918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event (id UUID NOT NULL, larp_id UUID NOT NULL, thread_id UUID DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA763FF2A01 ON event (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA7E2904019 ON event (thread_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.thread_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_larp_participant (event_id UUID NOT NULL, larp_participant_id UUID NOT NULL, PRIMARY KEY(event_id, larp_participant_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EC0F7C3571F7E88B ON event_larp_participant (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EC0F7C35A08CBE59 ON event_larp_participant (larp_participant_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_participant.event_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_participant.larp_participant_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_larp_character (event_id UUID NOT NULL, larp_character_id UUID NOT NULL, PRIMARY KEY(event_id, larp_character_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_708DE6AC71F7E88B ON event_larp_character (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_708DE6ACB89790D2 ON event_larp_character (larp_character_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_character.event_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_character.larp_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_larp_faction (event_id UUID NOT NULL, larp_faction_id UUID NOT NULL, PRIMARY KEY(event_id, larp_faction_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BAB5871971F7E88B ON event_larp_faction (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BAB5871973AC70CA ON event_larp_faction (larp_faction_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_faction.event_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_larp_faction.larp_faction_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE item (id UUID NOT NULL, larp_id UUID NOT NULL, is_crafted BOOLEAN NOT NULL, is_purchased BOOLEAN NOT NULL, quantity INT NOT NULL, cost_amount VARCHAR(255) NOT NULL, cost_currency VARCHAR(3) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1F1B251E63FF2A01 ON item (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN item.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN item.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE item_story_object (item_id UUID NOT NULL, story_object_id UUID NOT NULL, PRIMARY KEY(item_id, story_object_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_28F4E4DF126F525E ON item_story_object (item_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_28F4E4DFDA976C5A ON item_story_object (story_object_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN item_story_object.item_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN item_story_object.story_object_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_tags (larp_character_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(larp_character_id, tag_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_45D00EFEB89790D2 ON larp_character_tags (larp_character_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_45D00EFEBAD26311 ON larp_character_tags (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_tags.larp_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_tags.tag_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_item (id UUID NOT NULL, character_id UUID NOT NULL, item_id UUID NOT NULL, amount INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3577BFC61136BE75 ON larp_character_item (character_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3577BFC6126F525E ON larp_character_item (item_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_item.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_item.character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_item.item_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_skill (id UUID NOT NULL, character_id UUID NOT NULL, skill_id UUID NOT NULL, level INT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D61FD20B1136BE75 ON larp_character_skill (character_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D61FD20B5585C142 ON larp_character_skill (skill_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_skill.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_skill.character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_skill.skill_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quest (id UUID NOT NULL, larp_id UUID NOT NULL, thread_id UUID DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4317F81763FF2A01 ON quest (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4317F817E2904019 ON quest (thread_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN quest.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN quest.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN quest.thread_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE relation (id UUID NOT NULL, larp_id UUID NOT NULL, from_id UUID NOT NULL, to_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6289474963FF2A01 ON relation (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6289474978CED90B ON relation (from_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6289474930354A65 ON relation (to_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN relation.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN relation.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN relation.from_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN relation.to_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE skill (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5E3DE47763FF2A01 ON skill (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5E3DE477B03A8386 ON skill (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN skill.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN skill.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN skill.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE story_object (id UUID NOT NULL, created_by_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E7AE191EB03A8386 ON story_object (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN story_object.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN story_object.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, target VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_389B78363FF2A01 ON tag (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE thread (id UUID NOT NULL, larp_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_31204C8363FF2A01 ON thread (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN thread.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN thread.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA763FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7BF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_participant ADD CONSTRAINT FK_EC0F7C3571F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_participant ADD CONSTRAINT FK_EC0F7C35A08CBE59 FOREIGN KEY (larp_participant_id) REFERENCES larp_participant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_character ADD CONSTRAINT FK_708DE6AC71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_character ADD CONSTRAINT FK_708DE6ACB89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_faction ADD CONSTRAINT FK_BAB5871971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_faction ADD CONSTRAINT FK_BAB5871973AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item ADD CONSTRAINT FK_1F1B251E63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item ADD CONSTRAINT FK_1F1B251EBF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_story_object ADD CONSTRAINT FK_28F4E4DF126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_story_object ADD CONSTRAINT FK_28F4E4DFDA976C5A FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_tags ADD CONSTRAINT FK_45D00EFEB89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_tags ADD CONSTRAINT FK_45D00EFEBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_item ADD CONSTRAINT FK_3577BFC61136BE75 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_item ADD CONSTRAINT FK_3577BFC6126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_skill ADD CONSTRAINT FK_D61FD20B1136BE75 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_skill ADD CONSTRAINT FK_D61FD20B5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest ADD CONSTRAINT FK_4317F81763FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest ADD CONSTRAINT FK_4317F817E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest ADD CONSTRAINT FK_4317F817BF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD CONSTRAINT FK_6289474963FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD CONSTRAINT FK_6289474978CED90B FOREIGN KEY (from_id) REFERENCES story_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD CONSTRAINT FK_6289474930354A65 FOREIGN KEY (to_id) REFERENCES story_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD CONSTRAINT FK_62894749BF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47763FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE skill ADD CONSTRAINT FK_5E3DE477B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ADD CONSTRAINT FK_E7AE191EB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag ADD CONSTRAINT FK_389B78363FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag ADD CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread ADD CONSTRAINT FK_31204C8363FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread ADD CONSTRAINT FK_31204C83BF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP CONSTRAINT fk_afc950dfb03a8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_afc950df63ff2a015e237e06
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_afc950dfb03a8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD story_writer_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD gender VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD notes TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD character_type VARCHAR(255) NOT NULL DEFAULT 'player'
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO story_object (id, type, title, description, created_by_id, created_at, updated_at)
            SELECT id, 'character', name, description, created_by_id, created_at, updated_at
            FROM larp_character
            WHERE id NOT IN (SELECT id FROM story_object)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP created_by_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character.story_writer_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFE27DDCC7 FOREIGN KEY (story_writer_id) REFERENCES larp_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFBF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AFC950DFE27DDCC7 ON larp_character (story_writer_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant DROP CONSTRAINT fk_ca1f9ffd4448f8da
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_ca1f9ffd4448f8da
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant DROP faction_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFBF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA763FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7E2904019
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7BF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_participant DROP CONSTRAINT FK_EC0F7C3571F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_participant DROP CONSTRAINT FK_EC0F7C35A08CBE59
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_character DROP CONSTRAINT FK_708DE6AC71F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_character DROP CONSTRAINT FK_708DE6ACB89790D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_faction DROP CONSTRAINT FK_BAB5871971F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_larp_faction DROP CONSTRAINT FK_BAB5871973AC70CA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item DROP CONSTRAINT FK_1F1B251E63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item DROP CONSTRAINT FK_1F1B251EBF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_story_object DROP CONSTRAINT FK_28F4E4DF126F525E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_story_object DROP CONSTRAINT FK_28F4E4DFDA976C5A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_tags DROP CONSTRAINT FK_45D00EFEB89790D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_tags DROP CONSTRAINT FK_45D00EFEBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_item DROP CONSTRAINT FK_3577BFC61136BE75
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_item DROP CONSTRAINT FK_3577BFC6126F525E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_skill DROP CONSTRAINT FK_D61FD20B1136BE75
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_skill DROP CONSTRAINT FK_D61FD20B5585C142
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP CONSTRAINT FK_4317F81763FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP CONSTRAINT FK_4317F817E2904019
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP CONSTRAINT FK_4317F817BF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP CONSTRAINT FK_6289474963FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP CONSTRAINT FK_6289474978CED90B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP CONSTRAINT FK_6289474930354A65
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP CONSTRAINT FK_62894749BF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE skill DROP CONSTRAINT FK_5E3DE47763FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE skill DROP CONSTRAINT FK_5E3DE477B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object DROP CONSTRAINT FK_E7AE191EB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag DROP CONSTRAINT FK_389B78363FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag DROP CONSTRAINT FK_389B783B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread DROP CONSTRAINT FK_31204C8363FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread DROP CONSTRAINT FK_31204C83BF396750
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_larp_participant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_larp_character
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_larp_faction
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE item_story_object
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_tags
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_skill
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quest
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE relation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE skill
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE story_object
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE thread
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant ADD faction_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_participant.faction_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant ADD CONSTRAINT fk_ca1f9ffd4448f8da FOREIGN KEY (faction_id) REFERENCES larp_faction (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ca1f9ffd4448f8da ON larp_participant (faction_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFE27DDCC7
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_AFC950DFE27DDCC7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD created_by_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD description VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP story_writer_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP gender
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP notes
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character RENAME COLUMN character_type TO name
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD CONSTRAINT fk_afc950dfb03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_afc950df63ff2a015e237e06 ON larp_character (larp_id, name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_afc950dfb03a8386 ON larp_character (created_by_id)
        SQL);
    }
}
