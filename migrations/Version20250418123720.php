<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418123720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_AFC950DF63FF2A015E237E06 ON larp_character (larp_id, name)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ADD larp_character_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ADD invited_role VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN larp_invitation.larp_character_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ADD CONSTRAINT FK_C6F9E9B2B89790D2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C6F9E9B2B89790D2 ON larp_invitation (larp_character_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_AFC950DF63FF2A015E237E06
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation DROP CONSTRAINT FK_C6F9E9B2B89790D2
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_C6F9E9B2B89790D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation DROP larp_character_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation DROP invited_role
        SQL);
    }
}
