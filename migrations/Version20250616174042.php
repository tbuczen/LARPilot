<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250616174042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename submission tables to application';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE larp_character_submission_choice DROP CONSTRAINT IF EXISTS FK_8E143BBCE1FD4933");
        $this->addSql("ALTER TABLE larp_participant DROP CONSTRAINT IF EXISTS FK_CA1F9FFD1A4302EA");
        $this->addSql("ALTER TABLE larp_character_submission DROP CONSTRAINT IF EXISTS FK_FAF4708D63FF2A01");
        $this->addSql("ALTER TABLE larp_character_submission DROP CONSTRAINT IF EXISTS FK_FAF4708DA76ED395");
        $this->addSql("ALTER TABLE larp_character_submission DROP CONSTRAINT IF EXISTS FK_FAF4708D2788656B");
        $this->addSql("ALTER TABLE larp_character_submission DROP CONSTRAINT IF EXISTS FK_FAF4708D9FC692C9");

        $this->addSql("ALTER TABLE larp_character_submission RENAME TO larp_application");
        $this->addSql("ALTER TABLE larp_character_submission_choice RENAME TO larp_application_choice");
        $this->addSql("ALTER TABLE larp_application_choice RENAME COLUMN submission_id TO application_id");
        $this->addSql("ALTER TABLE larp_participant RENAME COLUMN larp_character_submission_id TO larp_application_id");
        $this->addSql("ALTER INDEX UNIQ_CA1F9FFD1A4302EA RENAME TO UNIQ_CA1F9FFDA2E69AF0");

        $this->addSql("ALTER TABLE larp_application ADD CONSTRAINT FK_7A0EE75463FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_application ADD CONSTRAINT FK_7A0EE754A76ED395 FOREIGN KEY (user_id) REFERENCES \"user\" (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_application ADD CONSTRAINT FK_7A0EE7542788656B FOREIGN KEY (preferred_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_application ADD CONSTRAINT FK_7A0EE7549FC692C9 FOREIGN KEY (unwanted_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_application_choice ADD CONSTRAINT FK_D34639A7A2E69AF0 FOREIGN KEY (application_id) REFERENCES larp_application (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_application_choice ADD CONSTRAINT FK_D34639A71136BE75 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFDA2E69AF0 FOREIGN KEY (larp_application_id) REFERENCES larp_application (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE larp_participant DROP CONSTRAINT IF EXISTS FK_CA1F9FFDA2E69AF0");
        $this->addSql("ALTER TABLE larp_application_choice DROP CONSTRAINT IF EXISTS FK_D34639A7A2E69AF0");
        $this->addSql("ALTER TABLE larp_application_choice DROP CONSTRAINT IF EXISTS FK_D34639A71136BE75");
        $this->addSql("ALTER TABLE larp_application DROP CONSTRAINT IF EXISTS FK_7A0EE75463FF2A01");
        $this->addSql("ALTER TABLE larp_application DROP CONSTRAINT IF EXISTS FK_7A0EE754A76ED395");
        $this->addSql("ALTER TABLE larp_application DROP CONSTRAINT IF EXISTS FK_7A0EE7542788656B");
        $this->addSql("ALTER TABLE larp_application DROP CONSTRAINT IF EXISTS FK_7A0EE7549FC692C9");

        $this->addSql("ALTER TABLE larp_application_choice RENAME COLUMN application_id TO submission_id");
        $this->addSql("ALTER TABLE larp_participant RENAME COLUMN larp_application_id TO larp_character_submission_id");
        $this->addSql("ALTER TABLE larp_application RENAME TO larp_character_submission");
        $this->addSql("ALTER TABLE larp_application_choice RENAME TO larp_character_submission_choice");
        $this->addSql("ALTER INDEX UNIQ_CA1F9FFDA2E69AF0 RENAME TO UNIQ_CA1F9FFD1A4302EA");

        $this->addSql("ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708DA76ED395 FOREIGN KEY (user_id) REFERENCES \"user\" (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D2788656B FOREIGN KEY (preferred_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D9FC692C9 FOREIGN KEY (unwanted_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_character_submission_choice ADD CONSTRAINT FK_8E143BBCE1FD4933 FOREIGN KEY (submission_id) REFERENCES larp_character_submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_character_submission_choice ADD CONSTRAINT FK_8E143BBC1136BE75 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFD1A4302EA FOREIGN KEY (larp_character_submission_id) REFERENCES larp_character_submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
