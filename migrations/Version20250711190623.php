<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250711190623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_event_tags_event_id RENAME TO IDX_7077D88D71F7E88B');
        $this->addSql('ALTER INDEX idx_event_tags_tag_id RENAME TO IDX_7077D88DBAD26311');
        $this->addSql('ALTER TABLE kanban_task ALTER status TYPE VARCHAR(255)');
        $this->addSql('ALTER INDEX idx_kanban_task_larp_id RENAME TO IDX_F67E477663FF2A01');
        $this->addSql('ALTER TABLE larp ALTER max_character_choices DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_faf4708db03a8386 RENAME TO IDX_B9DB292DB03A8386');
        $this->addSql('ALTER INDEX idx_faf4708d63ff2a01 RENAME TO IDX_B9DB292D63FF2A01');
        $this->addSql('ALTER INDEX idx_faf4708da76ed395 RENAME TO IDX_B9DB292DA76ED395');
        $this->addSql('ALTER INDEX idx_faf4708d2788656b RENAME TO IDX_B9DB292D2788656B');
        $this->addSql('ALTER INDEX idx_faf4708d9fc692c9 RENAME TO IDX_B9DB292D9FC692C9');
        $this->addSql('ALTER TABLE larp_application_choice DROP CONSTRAINT fk_d34639a71136be75');
        $this->addSql('ALTER TABLE larp_application_choice ADD votes INT NOT NULL');
        $this->addSql('ALTER INDEX idx_8e143bbce1fd4933 RENAME TO IDX_280EFEEB3E030ACD');
        $this->addSql('ALTER INDEX idx_8e143bbc1136be75 RENAME TO IDX_280EFEEB1136BE75');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C6F9E9B277153098 ON larp_invitation (code)');
        $this->addSql('ALTER INDEX uniq_ca1f9ffda2e69af0 RENAME TO UNIQ_CA1F9FFD393848D');
        $this->addSql('ALTER INDEX idx_quest_tags_quest_id RENAME TO IDX_61B638D4209E9EF4');
        $this->addSql('ALTER INDEX idx_quest_tags_tag_id RENAME TO IDX_61B638D4BAD26311');
        $this->addSql('CREATE SEQUENCE story_object_log_entry_id_seq');
        $this->addSql('SELECT setval(\'story_object_log_entry_id_seq\', (SELECT MAX(id) FROM story_object_log_entry))');
        $this->addSql('ALTER TABLE story_object_log_entry ALTER id SET DEFAULT nextval(\'story_object_log_entry_id_seq\')');
        $this->addSql('ALTER TABLE story_object_log_entry ALTER data TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN story_object_log_entry.data IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE tag RENAME COLUMN name TO title');
        $this->addSql('ALTER INDEX idx_thread_tags_thread_id RENAME TO IDX_EB0D88A8E2904019');
        $this->addSql('ALTER INDEX idx_thread_tags_tag_id RENAME TO IDX_EB0D88A8BAD26311');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE kanban_task ALTER status TYPE VARCHAR(20)');
        $this->addSql('ALTER INDEX idx_f67e477663ff2a01 RENAME TO idx_kanban_task_larp_id');
        $this->addSql('ALTER TABLE story_object_log_entry ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE story_object_log_entry ALTER data TYPE JSON');
        $this->addSql('COMMENT ON COLUMN story_object_log_entry.data IS NULL');
        $this->addSql('ALTER INDEX uniq_ca1f9ffd393848d RENAME TO uniq_ca1f9ffda2e69af0');
        $this->addSql('ALTER TABLE tag RENAME COLUMN title TO name');
        $this->addSql('ALTER TABLE larp_application_choice DROP votes');
        $this->addSql('ALTER INDEX idx_280efeeb1136be75 RENAME TO idx_8e143bbc1136be75');
        $this->addSql('ALTER INDEX idx_280efeeb3e030acd RENAME TO idx_8e143bbce1fd4933');
        $this->addSql('ALTER INDEX idx_eb0d88a8bad26311 RENAME TO idx_thread_tags_tag_id');
        $this->addSql('ALTER INDEX idx_eb0d88a8e2904019 RENAME TO idx_thread_tags_thread_id');
        $this->addSql('ALTER TABLE larp ALTER max_character_choices SET DEFAULT 3');
        $this->addSql('ALTER INDEX idx_61b638d4209e9ef4 RENAME TO idx_quest_tags_quest_id');
        $this->addSql('ALTER INDEX idx_61b638d4bad26311 RENAME TO idx_quest_tags_tag_id');
        $this->addSql('ALTER INDEX idx_7077d88d71f7e88b RENAME TO idx_event_tags_event_id');
        $this->addSql('ALTER INDEX idx_7077d88dbad26311 RENAME TO idx_event_tags_tag_id');
        $this->addSql('ALTER INDEX idx_b9db292d2788656b RENAME TO idx_faf4708d2788656b');
        $this->addSql('ALTER INDEX idx_b9db292d63ff2a01 RENAME TO idx_faf4708d63ff2a01');
        $this->addSql('ALTER INDEX idx_b9db292d9fc692c9 RENAME TO idx_faf4708d9fc692c9');
        $this->addSql('ALTER INDEX idx_b9db292da76ed395 RENAME TO idx_faf4708da76ed395');
        $this->addSql('ALTER INDEX idx_b9db292db03a8386 RENAME TO idx_faf4708db03a8386');
        $this->addSql('DROP INDEX UNIQ_C6F9E9B277153098');
    }
}
