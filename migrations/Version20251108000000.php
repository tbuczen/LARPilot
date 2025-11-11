<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Comment table - POC for discussions and comments on StoryObjects
 */
final class Version20251108000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create comment table for discussions on story objects';
    }

    public function up(Schema $schema): void
    {
        // Create comment table
        $this->addSql('CREATE TABLE comment (
            id UUID NOT NULL,
            story_object_id UUID NOT NULL,
            author_id UUID NOT NULL,
            parent_id UUID DEFAULT NULL,
            content TEXT NOT NULL,
            is_resolved BOOLEAN NOT NULL DEFAULT false,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C9D86650F FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes
        $this->addSql('CREATE INDEX idx_comment_story_object ON comment (story_object_id)');
        $this->addSql('CREATE INDEX idx_comment_parent ON comment (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the comment table and all its constraints
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C9D86650F');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C727ACA70');
        $this->addSql('DROP TABLE comment');
    }
}
