<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118175300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE survey (id UUID NOT NULL, larp_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AD5F9BFC63FF2A01 ON survey (larp_id)');
        $this->addSql('CREATE INDEX IDX_AD5F9BFC63FF2A01 ON survey (larp_id)');
        $this->addSql('COMMENT ON COLUMN survey.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_answer (id UUID NOT NULL, response_id UUID NOT NULL, question_id UUID NOT NULL, answer_text TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F2D38249FBF32840 ON survey_answer (response_id)');
        $this->addSql('CREATE INDEX IDX_F2D382491E27F6BF ON survey_answer (question_id)');
        $this->addSql('COMMENT ON COLUMN survey_answer.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_answer.response_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_answer.question_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_answer_selected_options (survey_answer_id UUID NOT NULL, survey_question_option_id UUID NOT NULL, PRIMARY KEY(survey_answer_id, survey_question_option_id))');
        $this->addSql('CREATE INDEX IDX_2F0C3EE9F650A2A ON survey_answer_selected_options (survey_answer_id)');
        $this->addSql('CREATE INDEX IDX_2F0C3EE9BFC3A540 ON survey_answer_selected_options (survey_question_option_id)');
        $this->addSql('COMMENT ON COLUMN survey_answer_selected_options.survey_answer_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_answer_selected_options.survey_question_option_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_answer_selected_tags (survey_answer_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(survey_answer_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_5C1B784CF650A2A ON survey_answer_selected_tags (survey_answer_id)');
        $this->addSql('CREATE INDEX IDX_5C1B784CBAD26311 ON survey_answer_selected_tags (tag_id)');
        $this->addSql('COMMENT ON COLUMN survey_answer_selected_tags.survey_answer_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_answer_selected_tags.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_question (id UUID NOT NULL, survey_id UUID NOT NULL, question_text TEXT NOT NULL, help_text TEXT DEFAULT NULL, question_type VARCHAR(50) NOT NULL, is_required BOOLEAN NOT NULL, order_position INT NOT NULL, tag_category VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EA000F69B3FE509D ON survey_question (survey_id)');
        $this->addSql('CREATE INDEX IDX_EA000F69B3FE509DA7D40644 ON survey_question (survey_id, order_position)');
        $this->addSql('COMMENT ON COLUMN survey_question.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_question.survey_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_question_option (id UUID NOT NULL, question_id UUID NOT NULL, option_text VARCHAR(255) NOT NULL, order_position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F50FFD8C1E27F6BF ON survey_question_option (question_id)');
        $this->addSql('CREATE INDEX IDX_F50FFD8C1E27F6BFA7D40644 ON survey_question_option (question_id, order_position)');
        $this->addSql('COMMENT ON COLUMN survey_question_option.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_question_option.question_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE survey_response (id UUID NOT NULL, survey_id UUID NOT NULL, larp_id UUID NOT NULL, user_id UUID NOT NULL, assigned_character_id UUID DEFAULT NULL, status VARCHAR(50) NOT NULL, match_suggestions JSON DEFAULT NULL, organizer_notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_628C4DDCF3A9FE81 ON survey_response (assigned_character_id)');
        $this->addSql('CREATE INDEX IDX_628C4DDC63FF2A01 ON survey_response (larp_id)');
        $this->addSql('CREATE INDEX IDX_628C4DDCA76ED395 ON survey_response (user_id)');
        $this->addSql('CREATE INDEX IDX_628C4DDCB3FE509D ON survey_response (survey_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_628C4DDC63FF2A01A76ED395 ON survey_response (larp_id, user_id)');
        $this->addSql('COMMENT ON COLUMN survey_response.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_response.survey_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_response.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_response.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN survey_response.assigned_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE survey ADD CONSTRAINT FK_AD5F9BFC63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer ADD CONSTRAINT FK_F2D38249FBF32840 FOREIGN KEY (response_id) REFERENCES survey_response (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer ADD CONSTRAINT FK_F2D382491E27F6BF FOREIGN KEY (question_id) REFERENCES survey_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer_selected_options ADD CONSTRAINT FK_2F0C3EE9F650A2A FOREIGN KEY (survey_answer_id) REFERENCES survey_answer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer_selected_options ADD CONSTRAINT FK_2F0C3EE9BFC3A540 FOREIGN KEY (survey_question_option_id) REFERENCES survey_question_option (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer_selected_tags ADD CONSTRAINT FK_5C1B784CF650A2A FOREIGN KEY (survey_answer_id) REFERENCES survey_answer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_answer_selected_tags ADD CONSTRAINT FK_5C1B784CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_question ADD CONSTRAINT FK_EA000F69B3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_question_option ADD CONSTRAINT FK_F50FFD8C1E27F6BF FOREIGN KEY (question_id) REFERENCES survey_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_response ADD CONSTRAINT FK_628C4DDCB3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_response ADD CONSTRAINT FK_628C4DDC63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_response ADD CONSTRAINT FK_628C4DDCA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE survey_response ADD CONSTRAINT FK_628C4DDCF3A9FE81 FOREIGN KEY (assigned_character_id) REFERENCES character (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER story_object_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER author_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER parent_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER is_resolved DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN comment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN comment.story_object_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN comment.author_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN comment.parent_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE gallery ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE gallery ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER INDEX idx_472b783a5a6d2c8f RENAME TO IDX_472B783A63FF2A01');
        $this->addSql('ALTER INDEX idx_472b783abf03b4b0 RENAME TO IDX_472B783A53EC1A21');
        $this->addSql('ALTER TABLE larp ADD application_mode VARCHAR(50) DEFAULT \'character_selection\' NOT NULL');
        $this->addSql('ALTER TABLE larp ADD publish_characters_publicly BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER application_mode DROP DEFAULT');
        $this->addSql('ALTER TABLE larp ALTER publish_characters_publicly DROP DEFAULT');
        $this->addSql('ALTER TABLE location ALTER approval_status SET DEFAULT \'pending\'');
        $this->addSql('ALTER INDEX idx_5e9e89cb2d1630ab RENAME TO IDX_5E9E89CB2D234F6A');
        $this->addSql('ALTER TABLE mail_template ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE mail_template ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER INDEX idx_5e1b4af45a6d2c8f RENAME TO IDX_4AB7DECB63FF2A01');
        $this->addSql('ALTER INDEX uniq_5e1b4af45a6d2c8ff5a7b40 RENAME TO uniq_mail_template_larp_type');
        $this->addSql('ALTER TABLE plan ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE plan ALTER has_google_integrations DROP DEFAULT');
        $this->addSql('ALTER TABLE plan ALTER has_custom_branding DROP DEFAULT');
        $this->addSql('ALTER TABLE plan ALTER price_in_cents DROP DEFAULT');
        $this->addSql('ALTER TABLE plan ALTER is_free DROP DEFAULT');
        $this->addSql('ALTER TABLE plan ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE plan ALTER sort_order DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN plan.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE "user" ALTER plan_id TYPE UUID');
        $this->addSql('ALTER TABLE "user" ALTER status DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN "user".plan_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE survey DROP CONSTRAINT FK_AD5F9BFC63FF2A01');
        $this->addSql('ALTER TABLE survey_answer DROP CONSTRAINT FK_F2D38249FBF32840');
        $this->addSql('ALTER TABLE survey_answer DROP CONSTRAINT FK_F2D382491E27F6BF');
        $this->addSql('ALTER TABLE survey_answer_selected_options DROP CONSTRAINT FK_2F0C3EE9F650A2A');
        $this->addSql('ALTER TABLE survey_answer_selected_options DROP CONSTRAINT FK_2F0C3EE9BFC3A540');
        $this->addSql('ALTER TABLE survey_answer_selected_tags DROP CONSTRAINT FK_5C1B784CF650A2A');
        $this->addSql('ALTER TABLE survey_answer_selected_tags DROP CONSTRAINT FK_5C1B784CBAD26311');
        $this->addSql('ALTER TABLE survey_question DROP CONSTRAINT FK_EA000F69B3FE509D');
        $this->addSql('ALTER TABLE survey_question_option DROP CONSTRAINT FK_F50FFD8C1E27F6BF');
        $this->addSql('ALTER TABLE survey_response DROP CONSTRAINT FK_628C4DDCB3FE509D');
        $this->addSql('ALTER TABLE survey_response DROP CONSTRAINT FK_628C4DDC63FF2A01');
        $this->addSql('ALTER TABLE survey_response DROP CONSTRAINT FK_628C4DDCA76ED395');
        $this->addSql('ALTER TABLE survey_response DROP CONSTRAINT FK_628C4DDCF3A9FE81');
        $this->addSql('DROP TABLE survey');
        $this->addSql('DROP TABLE survey_answer');
        $this->addSql('DROP TABLE survey_answer_selected_options');
        $this->addSql('DROP TABLE survey_answer_selected_tags');
        $this->addSql('DROP TABLE survey_question');
        $this->addSql('DROP TABLE survey_question_option');
        $this->addSql('DROP TABLE survey_response');
        $this->addSql('ALTER TABLE larp DROP application_mode');
        $this->addSql('ALTER TABLE larp DROP publish_characters_publicly');
        $this->addSql('ALTER TABLE gallery ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE gallery ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER INDEX idx_472b783a63ff2a01 RENAME TO idx_472b783a5a6d2c8f');
        $this->addSql('ALTER INDEX idx_472b783a53ec1a21 RENAME TO idx_472b783abf03b4b0');
        $this->addSql('ALTER TABLE comment ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER story_object_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER author_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER parent_id TYPE UUID');
        $this->addSql('ALTER TABLE comment ALTER is_resolved SET DEFAULT false');
        $this->addSql('COMMENT ON COLUMN comment.id IS NULL');
        $this->addSql('COMMENT ON COLUMN comment.story_object_id IS NULL');
        $this->addSql('COMMENT ON COLUMN comment.author_id IS NULL');
        $this->addSql('COMMENT ON COLUMN comment.parent_id IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER plan_id TYPE UUID');
        $this->addSql('ALTER TABLE "user" ALTER status SET DEFAULT \'pending\'');
        $this->addSql('COMMENT ON COLUMN "user".plan_id IS NULL');
        $this->addSql('ALTER TABLE plan ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE plan ALTER has_google_integrations SET DEFAULT false');
        $this->addSql('ALTER TABLE plan ALTER has_custom_branding SET DEFAULT false');
        $this->addSql('ALTER TABLE plan ALTER price_in_cents SET DEFAULT 0');
        $this->addSql('ALTER TABLE plan ALTER is_free SET DEFAULT true');
        $this->addSql('ALTER TABLE plan ALTER is_active SET DEFAULT true');
        $this->addSql('ALTER TABLE plan ALTER sort_order SET DEFAULT 0');
        $this->addSql('COMMENT ON COLUMN plan.id IS NULL');
        $this->addSql('ALTER TABLE mail_template ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE mail_template ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER INDEX idx_4ab7decb63ff2a01 RENAME TO idx_5e1b4af45a6d2c8f');
        $this->addSql('ALTER INDEX uniq_mail_template_larp_type RENAME TO uniq_5e1b4af45a6d2c8ff5a7b40');
        $this->addSql('ALTER TABLE location ALTER approval_status SET DEFAULT \'approved\'');
        $this->addSql('ALTER INDEX idx_5e9e89cb2d234f6a RENAME TO idx_5e9e89cb2d1630ab');
    }
}
