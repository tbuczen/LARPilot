<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251115120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mail_template table for the new mailing domain';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mail_template (
            id UUID NOT NULL,
            larp_id UUID NOT NULL,
            type VARCHAR(64) NOT NULL,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            enabled BOOLEAN DEFAULT true NOT NULL,
            available_placeholders JSON DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_5E1B4AF45A6D2C8F ON mail_template (larp_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E1B4AF45A6D2C8FF5A7B40 ON mail_template (larp_id, type)');
        $this->addSql("COMMENT ON COLUMN mail_template.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN mail_template.larp_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_5E1B4AF45A6D2C8F FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_template DROP CONSTRAINT FK_5E1B4AF45A6D2C8F');
        $this->addSql('DROP TABLE mail_template');
    }
}
