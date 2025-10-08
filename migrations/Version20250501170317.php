<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501170317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_larp DROP CONSTRAINT fk_5cd0773a73ac70ca
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_larp DROP CONSTRAINT fk_5cd0773a63ff2a01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD larp_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE larp_faction set larp_id = (select larp_id from larp_faction_larp where larp_faction_id = larp_faction.id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE larp_faction_larp
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction ADD CONSTRAINT FK_57A68DEA63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_57A68DEA63FF2A01 ON larp_faction (larp_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE larp_faction_larp (larp_faction_id UUID NOT NULL, larp_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, larp_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_5cd0773a73ac70ca ON larp_faction_larp (larp_faction_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_5cd0773a63ff2a01 ON larp_faction_larp (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_larp.larp_faction_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_faction_larp.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_larp ADD CONSTRAINT fk_5cd0773a73ac70ca FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction_larp ADD CONSTRAINT fk_5cd0773a63ff2a01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP CONSTRAINT FK_57A68DEA63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_57A68DEA63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_faction DROP larp_id
        SQL);
    }
}
