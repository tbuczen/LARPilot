<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250616150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tag relations to event, quest and thread';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE event_tags (event_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(event_id, tag_id))
        SQL);
        $this->addSql("CREATE INDEX IDX_EVENT_TAGS_EVENT_ID ON event_tags (event_id)");
        $this->addSql("CREATE INDEX IDX_EVENT_TAGS_TAG_ID ON event_tags (tag_id)");
        $this->addSql("COMMENT ON COLUMN event_tags.event_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN event_tags.tag_id IS '(DC2Type:uuid)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE quest_tags (quest_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(quest_id, tag_id))
        SQL);
        $this->addSql("CREATE INDEX IDX_QUEST_TAGS_QUEST_ID ON quest_tags (quest_id)");
        $this->addSql("CREATE INDEX IDX_QUEST_TAGS_TAG_ID ON quest_tags (tag_id)");
        $this->addSql("COMMENT ON COLUMN quest_tags.quest_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN quest_tags.tag_id IS '(DC2Type:uuid)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE thread_tags (thread_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(thread_id, tag_id))
        SQL);
        $this->addSql("CREATE INDEX IDX_THREAD_TAGS_THREAD_ID ON thread_tags (thread_id)");
        $this->addSql("CREATE INDEX IDX_THREAD_TAGS_TAG_ID ON thread_tags (tag_id)");
        $this->addSql("COMMENT ON COLUMN thread_tags.thread_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN thread_tags.tag_id IS '(DC2Type:uuid)'");

        $this->addSql(<<<'SQL'
            ALTER TABLE event_tags ADD CONSTRAINT FK_EVENT_TAGS_EVENT_ID FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_tags ADD CONSTRAINT FK_EVENT_TAGS_TAG_ID FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_tags ADD CONSTRAINT FK_QUEST_TAGS_QUEST_ID FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_tags ADD CONSTRAINT FK_QUEST_TAGS_TAG_ID FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread_tags ADD CONSTRAINT FK_THREAD_TAGS_THREAD_ID FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread_tags ADD CONSTRAINT FK_THREAD_TAGS_TAG_ID FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE event_tags DROP CONSTRAINT FK_EVENT_TAGS_EVENT_ID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_tags DROP CONSTRAINT FK_EVENT_TAGS_TAG_ID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_tags DROP CONSTRAINT FK_QUEST_TAGS_QUEST_ID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_tags DROP CONSTRAINT FK_QUEST_TAGS_TAG_ID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread_tags DROP CONSTRAINT FK_THREAD_TAGS_THREAD_ID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread_tags DROP CONSTRAINT FK_THREAD_TAGS_TAG_ID
        SQL);
        $this->addSql("DROP TABLE event_tags");
        $this->addSql("DROP TABLE quest_tags");
        $this->addSql("DROP TABLE thread_tags");
    }
}

