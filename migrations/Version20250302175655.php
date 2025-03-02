<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302175655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE larp ADD created_by_id UUID NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER slug SET NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER updated_at SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN larp.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp ADD CONSTRAINT FK_54AD5CF8B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_54AD5CF8B03A8386 ON larp (created_by_id)');
        $this->addSql('ALTER TABLE larp_character ADD created_by_id UUID NOT NULL');
        $this->addSql('ALTER TABLE larp_character ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL default NOW()');
        $this->addSql('ALTER TABLE larp_character ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL default NOW()');
        $this->addSql('COMMENT ON COLUMN larp_character.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AFC950DFB03A8386 ON larp_character (created_by_id)');
        $this->addSql('ALTER TABLE larp_character_submission ADD created_by_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN larp_character_submission.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708DB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FAF4708DB03A8386 ON larp_character_submission (created_by_id)');
        $this->addSql('ALTER TABLE larp_faction ADD created_by_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN larp_faction.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_faction ADD CONSTRAINT FK_57A68DEAB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_57A68DEAB03A8386 ON larp_faction (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_character_submission DROP CONSTRAINT FK_FAF4708DB03A8386');
        $this->addSql('DROP INDEX IDX_FAF4708DB03A8386');
        $this->addSql('ALTER TABLE larp_character_submission DROP created_by_id');
        $this->addSql('ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFB03A8386');
        $this->addSql('DROP INDEX IDX_AFC950DFB03A8386');
        $this->addSql('ALTER TABLE larp_character DROP created_by_id');
        $this->addSql('ALTER TABLE larp_character DROP created_at');
        $this->addSql('ALTER TABLE larp_character DROP updated_at');
        $this->addSql('ALTER TABLE larp DROP CONSTRAINT FK_54AD5CF8B03A8386');
        $this->addSql('DROP INDEX IDX_54AD5CF8B03A8386');
        $this->addSql('ALTER TABLE larp DROP created_by_id');
        $this->addSql('ALTER TABLE larp ALTER slug DROP NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE larp ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE larp_faction DROP CONSTRAINT FK_57A68DEAB03A8386');
        $this->addSql('DROP INDEX IDX_57A68DEAB03A8386');
        $this->addSql('ALTER TABLE larp_faction DROP created_by_id');
    }
}
