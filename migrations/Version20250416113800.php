<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416113800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD reference_type VARCHAR(255) NOT NULL default 'url'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD role VARCHAR(255) NOT NULL default 'mention'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP type
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference ADD type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP reference_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_reference DROP role
        SQL);
    }
}
