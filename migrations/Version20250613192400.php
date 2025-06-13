<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250613192400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add decision_tree column to quest and thread tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE quest ADD decision_tree JSONB DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread ADD decision_tree JSONB DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE quest DROP decision_tree
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE thread DROP decision_tree
        SQL);
    }
}
