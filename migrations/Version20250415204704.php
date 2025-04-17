<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415204704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE external_reference (id UUID NOT NULL, created_by_id UUID NOT NULL, target_type VARCHAR(255) NOT NULL, target_id UUID NOT NULL, provider VARCHAR(255) NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8AF8E607B03A8386 ON external_reference (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN external_reference.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN external_reference.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN external_reference.target_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD CONSTRAINT FK_8AF8E607B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD in_game_name VARCHAR(255) NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shared_file ADD url VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP CONSTRAINT FK_8AF8E607B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE external_reference
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shared_file DROP url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP in_game_name
        SQL);
    }
}
