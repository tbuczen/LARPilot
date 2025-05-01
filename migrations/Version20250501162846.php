<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501162846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD story_object_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN external_reference.story_object_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD CONSTRAINT FK_8AF8E607DA976C5A FOREIGN KEY (story_object_id) REFERENCES story_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8AF8E607DA976C5A ON external_reference (story_object_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ALTER character_type DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP CONSTRAINT fk_57a68deab03a8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_57a68deab03a8386
        SQL);
        $this->addSql(<<<'SQL'
    INSERT INTO story_object (id, type, title, description, created_by_id, created_at, updated_at)
    SELECT id, 'faction', name, description, created_by_id, NOW(), NOW()
    FROM larp_faction
    WHERE id NOT IN (SELECT id FROM story_object)
SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP created_by_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD CONSTRAINT FK_57A68DEABF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP CONSTRAINT FK_8AF8E607DA976C5A
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8AF8E607DA976C5A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP story_object_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ALTER character_type SET DEFAULT 'player'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP CONSTRAINT FK_57A68DEABF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD created_by_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD name VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD CONSTRAINT fk_57a68deab03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_57a68deab03a8386 ON larp_faction (created_by_id)
        SQL);
    }
}
