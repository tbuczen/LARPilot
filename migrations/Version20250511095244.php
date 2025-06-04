<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250511095244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP target_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP target_id
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AFC950DFD7ACF689 ON larp_character (in_game_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E7AE191E2B36786B ON story_object (title)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD target_type VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD target_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN external_reference.target_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_AFC950DFD7ACF689
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E7AE191E2B36786B
        SQL);
    }
}
