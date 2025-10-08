<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250614190001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add relation_type column to relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE relation ADD relation_type VARCHAR(255) NOT NULL DEFAULT 'friend'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE relation DROP relation_type
        SQL);
    }
}
