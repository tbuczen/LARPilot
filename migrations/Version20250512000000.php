<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250512000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move larp_id from sub tables to story_object';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ADD larp_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN story_object.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = e.larp_id FROM event e WHERE e.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = i.larp_id FROM item i WHERE i.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = lc.larp_id FROM larp_character lc WHERE lc.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = lf.larp_id FROM larp_faction lf WHERE lf.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = q.larp_id FROM quest q WHERE q.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = r.larp_id FROM relation r WHERE r.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE story_object s SET larp_id = t.larp_id FROM thread t WHERE t.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ALTER COLUMN larp_id SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object ADD CONSTRAINT FK_E7AE191E63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E7AE191E63FF2A01 ON story_object (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT IF EXISTS FK_3BAE0AA763FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item DROP CONSTRAINT IF EXISTS FK_1F1B251E63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP CONSTRAINT IF EXISTS FK_AFC950DF63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP CONSTRAINT IF EXISTS FK_57A68DEA63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP CONSTRAINT IF EXISTS FK_4317F81763FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP CONSTRAINT IF EXISTS FK_6289474963FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread DROP CONSTRAINT IF EXISTS FK_31204C8363FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP larp_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread DROP larp_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread ADD larp_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN item.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_character.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN quest.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN relation.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN thread.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE event e SET larp_id = s.larp_id FROM story_object s WHERE e.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE item i SET larp_id = s.larp_id FROM story_object s WHERE i.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE larp_character lc SET larp_id = s.larp_id FROM story_object s WHERE lc.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE larp_faction lf SET larp_id = s.larp_id FROM story_object s WHERE lf.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE quest q SET larp_id = s.larp_id FROM story_object s WHERE q.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE relation r SET larp_id = s.larp_id FROM story_object s WHERE r.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE thread t SET larp_id = s.larp_id FROM story_object s WHERE t.id = s.id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object DROP CONSTRAINT FK_E7AE191E63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E7AE191E63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story_object DROP larp_id
        SQL);
    }
}
