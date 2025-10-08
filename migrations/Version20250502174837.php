<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250502174837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE saved_form_filter (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, name VARCHAR(255) NOT NULL, form_name VARCHAR(100) NOT NULL, parameters JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D338D8AC63FF2A01 ON saved_form_filter (larp_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D338D8ACB03A8386 ON saved_form_filter (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN saved_form_filter.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN saved_form_filter.larp_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN saved_form_filter.created_by_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE saved_form_filter ADD CONSTRAINT FK_D338D8AC63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE saved_form_filter ADD CONSTRAINT FK_D338D8ACB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE saved_form_filter DROP CONSTRAINT FK_D338D8AC63FF2A01
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE saved_form_filter DROP CONSTRAINT FK_D338D8ACB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE saved_form_filter
        SQL);
    }
}
