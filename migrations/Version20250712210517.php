<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250712210517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag ALTER title DROP NOT NULL');
        $this->addSql('ALTER TABLE tag ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE tag ALTER description DROP NOT NULL');
        $this->addSql('ALTER TABLE tag ALTER description TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE tag ALTER title SET NOT NULL');
        $this->addSql('ALTER TABLE tag ALTER description TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE tag ALTER description SET NOT NULL');
    }
}
