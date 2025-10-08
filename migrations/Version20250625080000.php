<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add KanbanTask table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE kanban_task (id UUID NOT NULL, larp_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_KANBAN_TASK_LARP_ID ON kanban_task (larp_id)');
        $this->addSql('COMMENT ON COLUMN kanban_task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN kanban_task.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE kanban_task ADD CONSTRAINT FK_KANBAN_TASK_LARP_ID FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE kanban_task');
    }
}