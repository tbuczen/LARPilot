<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506072136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_quest (larp_character_id UUID NOT NULL, quest_id UUID NOT NULL, PRIMARY KEY(larp_character_id, quest_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CB35CE6BB89790D2 ON larp_character_quest (larp_character_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CB35CE6B209E9EF4 ON larp_character_quest (quest_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_quest.larp_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_quest.quest_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_character_thread (larp_character_id UUID NOT NULL, thread_id UUID NOT NULL, PRIMARY KEY(larp_character_id, thread_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_681B53A2B89790D2 ON larp_character_thread (larp_character_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_681B53A2E2904019 ON larp_character_thread (thread_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_thread.larp_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character_thread.thread_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_faction_quest (larp_faction_id UUID NOT NULL, quest_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, quest_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_367526A073AC70CA ON larp_faction_quest (larp_faction_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_367526A0209E9EF4 ON larp_faction_quest (quest_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_quest.larp_faction_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_quest.quest_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_faction_thread (larp_faction_id UUID NOT NULL, thread_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, thread_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6450087273AC70CA ON larp_faction_thread (larp_faction_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_64500872E2904019 ON larp_faction_thread (thread_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_thread.larp_faction_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_thread.thread_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_quest ADD CONSTRAINT FK_CB35CE6BB89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_quest ADD CONSTRAINT FK_CB35CE6B209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_thread ADD CONSTRAINT FK_681B53A2B89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_thread ADD CONSTRAINT FK_681B53A2E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_quest ADD CONSTRAINT FK_367526A073AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_quest ADD CONSTRAINT FK_367526A0209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_thread ADD CONSTRAINT FK_6450087273AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_thread ADD CONSTRAINT FK_64500872E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_quest DROP CONSTRAINT FK_CB35CE6BB89790D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_quest DROP CONSTRAINT FK_CB35CE6B209E9EF4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_thread DROP CONSTRAINT FK_681B53A2B89790D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character_thread DROP CONSTRAINT FK_681B53A2E2904019
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_quest DROP CONSTRAINT FK_367526A073AC70CA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_quest DROP CONSTRAINT FK_367526A0209E9EF4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_thread DROP CONSTRAINT FK_6450087273AC70CA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_thread DROP CONSTRAINT FK_64500872E2904019
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_quest
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_character_thread
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_faction_quest
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_faction_thread
        SQL);
    }
}
