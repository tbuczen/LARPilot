<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250616131736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add available_for_recruitment column to larp_character table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character ADD available_for_recruitment BOOLEAN NOT NULL DEFAULT FALSE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_character DROP available_for_recruitment
        SQL);
    }
}
