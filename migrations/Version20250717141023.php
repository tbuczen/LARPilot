<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250717141023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE kanban_task ADD assigned_to_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE kanban_task ADD priority INT NOT NULL');
        $this->addSql('ALTER TABLE kanban_task ADD due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE kanban_task ADD activity_log JSON DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN kanban_task.assigned_to_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE kanban_task ADD CONSTRAINT FK_F67E4776F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES larp_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F67E4776F4BD7827 ON kanban_task (assigned_to_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE kanban_task DROP CONSTRAINT FK_F67E4776F4BD7827');
        $this->addSql('DROP INDEX IDX_F67E4776F4BD7827');
        $this->addSql('ALTER TABLE kanban_task DROP assigned_to_id');
        $this->addSql('ALTER TABLE kanban_task DROP priority');
        $this->addSql('ALTER TABLE kanban_task DROP due_date');
        $this->addSql('ALTER TABLE kanban_task DROP activity_log');
    }
}
