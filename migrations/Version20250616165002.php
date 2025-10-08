<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616165002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_submission_choice (id UUID NOT NULL, submission_id UUID NOT NULL, character_id UUID NOT NULL, priority INT NOT NULL, justification TEXT DEFAULT NULL, visual TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8E143BBCE1FD4933 ON larp_character_submission_choice (submission_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8E143BBC1136BE75 ON larp_character_submission_choice (character_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_submission_choice.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_submission_choice.submission_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_submission_choice.character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_incident (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, report_code VARCHAR(64) NOT NULL, case_id VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, description TEXT NOT NULL, allow_feedback BOOLEAN NOT NULL, contact_accused BOOLEAN NOT NULL, allow_mediator BOOLEAN NOT NULL, stay_anonymous BOOLEAN NOT NULL, status VARCHAR(255) NOT NULL, needs_police_support BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D079B3ECF10D4F5 ON larp_incident (case_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D079B3E63FF2A01 ON larp_incident (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D079B3EB03A8386 ON larp_incident (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_incident.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_incident.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_incident.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_incident.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission_choice ADD CONSTRAINT FK_8E143BBCE1FD4933 FOREIGN KEY (submission_id) REFERENCES larp_character_submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission_choice ADD CONSTRAINT FK_8E143BBC1136BE75 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_incident ADD CONSTRAINT FK_8D079B3E63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_incident ADD CONSTRAINT FK_8D079B3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER place_id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.place_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp ADD max_character_choices SMALLINT NOT NULL DEFAULT 3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD continuation_character_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ALTER available_for_recruitment DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character.continuation_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFF6AA89A4 FOREIGN KEY (continuation_character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_AFC950DFF6AA89A4 ON larp_character (continuation_character_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD preferred_tags_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD unwanted_tags_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD favourite_style TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD triggers TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_submission.preferred_tags_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_submission.unwanted_tags_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D2788656B FOREIGN KEY (preferred_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D9FC692C9 FOREIGN KEY (unwanted_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FAF4708D2788656B ON larp_character_submission (preferred_tags_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FAF4708D9FC692C9 ON larp_character_submission (unwanted_tags_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant ADD larp_character_submission_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_participant.larp_character_submission_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFD1A4302EA FOREIGN KEY (larp_character_submission_id) REFERENCES larp_character_submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_CA1F9FFD1A4302EA ON larp_participant (larp_character_submission_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_8dce6f4ba0d0f5aa RENAME TO IDX_F3A816BC115985E8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_8dce6f4b1138c7e1 RENAME TO IDX_F3A816BC1136BE75
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ALTER relation_type DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ALTER larp_id SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_ef6e7dbf6cde5a RENAME TO IDX_370BE4D1DA976C5A
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission_choice DROP CONSTRAINT FK_8E143BBCE1FD4933
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission_choice DROP CONSTRAINT FK_8E143BBC1136BE75
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_incident DROP CONSTRAINT FK_8D079B3E63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_incident DROP CONSTRAINT FK_8D079B3EB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_submission_choice
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_incident
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ALTER larp_id DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_f3a816bc115985e8 RENAME TO idx_8dce6f4ba0d0f5aa
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_f3a816bc1136be75 RENAME TO idx_8dce6f4b1138c7e1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP CONSTRAINT FK_FAF4708D2788656B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP CONSTRAINT FK_FAF4708D9FC692C9
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FAF4708D2788656B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FAF4708D9FC692C9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP preferred_tags_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP unwanted_tags_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP favourite_style
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_submission DROP triggers
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp DROP max_character_choices
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_370be4d1da976c5a RENAME TO idx_ef6e7dbf6cde5a
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ALTER relation_type SET DEFAULT 'friend'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER place_id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.place_id IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant DROP CONSTRAINT FK_CA1F9FFD1A4302EA
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_CA1F9FFD1A4302EA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant DROP larp_character_submission_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFF6AA89A4
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_AFC950DFF6AA89A4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP continuation_character_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ALTER available_for_recruitment SET DEFAULT false
        SQL);
    }
}
