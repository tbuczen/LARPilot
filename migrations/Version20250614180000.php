<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250614180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add place story object';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE place (id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN place.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE place ADD CONSTRAINT FK_741D53CDBF396750 FOREIGN KEY (id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE place DROP CONSTRAINT FK_741D53CDBF396750
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE place
        SQL);
    }
}
