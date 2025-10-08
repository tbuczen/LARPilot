<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250301173408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_invitation (id UUID NOT NULL, larp_id UUID NOT NULL, code VARCHAR(64) NOT NULL, valid_to TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C6F9E9B263FF2A01 ON larp_invitation (larp_id)');
        $this->addSql('COMMENT ON COLUMN larp_invitation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_invitation.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_invitation.valid_to IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE larp_invitation ADD CONSTRAINT FK_C6F9E9B263FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp ADD slug VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE larp ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL');
        $this->addSql('ALTER TABLE larp ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54AD5CF8989D9B62 ON larp (slug)');
        $this->addSql('ALTER TABLE user_social_account ALTER display_name SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_invitation DROP CONSTRAINT FK_C6F9E9B263FF2A01');
        $this->addSql('DROP TABLE larp_invitation');
        $this->addSql('DROP INDEX UNIQ_54AD5CF8989D9B62');
        $this->addSql('ALTER TABLE larp DROP slug');
        $this->addSql('ALTER TABLE larp DROP created_at');
        $this->addSql('ALTER TABLE larp DROP updated_at');
        $this->addSql('ALTER TABLE user_social_account ALTER display_name DROP NOT NULL');
    }
}
