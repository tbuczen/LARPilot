<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418163056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ADD is_reusable BOOLEAN NOT NULL DEFAULT TRUE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ADD accepted_by_user_ids JSON DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation DROP is_reusable
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation DROP accepted_by_user_ids
        SQL);
    }
}
