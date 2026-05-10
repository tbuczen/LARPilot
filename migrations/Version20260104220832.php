<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add knowledge_document table for lore management with character/faction visibility controls
 */
final class Version20260104220832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create knowledge_document table with ManyToMany relationships to StoryObject for ownership and visibility';
    }

    public function up(Schema $schema): void
    {
        // Create knowledge_document table
        $this->addSql('CREATE TABLE knowledge_document (
            id UUID NOT NULL,
            larp_id UUID NOT NULL,
            creator_id UUID DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT DEFAULT NULL,
            is_public BOOLEAN DEFAULT false NOT NULL,
            category VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        // Indexes for performance
        $this->addSql('CREATE INDEX IDX_knowledge_document_title ON knowledge_document (title)');
        $this->addSql('CREATE INDEX IDX_knowledge_document_larp_id ON knowledge_document (larp_id)');

        // Foreign key constraints
        $this->addSql('ALTER TABLE knowledge_document ADD CONSTRAINT FK_knowledge_document_larp
            FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE knowledge_document ADD CONSTRAINT FK_knowledge_document_creator
            FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create join table for knowledge_document <-> story_object ownership
        $this->addSql('CREATE TABLE knowledge_document_owner (
            knowledge_document_id UUID NOT NULL,
            story_object_id UUID NOT NULL,
            PRIMARY KEY(knowledge_document_id, story_object_id)
        )');

        $this->addSql('CREATE INDEX IDX_knowledge_document_owner_kd ON knowledge_document_owner (knowledge_document_id)');
        $this->addSql('CREATE INDEX IDX_knowledge_document_owner_so ON knowledge_document_owner (story_object_id)');

        $this->addSql('ALTER TABLE knowledge_document_owner ADD CONSTRAINT FK_knowledge_document_owner_kd
            FOREIGN KEY (knowledge_document_id) REFERENCES knowledge_document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE knowledge_document_owner ADD CONSTRAINT FK_knowledge_document_owner_so
            FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create join table for knowledge_document <-> story_object visibility
        $this->addSql('CREATE TABLE knowledge_document_visible_to (
            knowledge_document_id UUID NOT NULL,
            story_object_id UUID NOT NULL,
            PRIMARY KEY(knowledge_document_id, story_object_id)
        )');

        $this->addSql('CREATE INDEX IDX_knowledge_document_visible_to_kd ON knowledge_document_visible_to (knowledge_document_id)');
        $this->addSql('CREATE INDEX IDX_knowledge_document_visible_to_so ON knowledge_document_visible_to (story_object_id)');

        $this->addSql('ALTER TABLE knowledge_document_visible_to ADD CONSTRAINT FK_knowledge_document_visible_to_kd
            FOREIGN KEY (knowledge_document_id) REFERENCES knowledge_document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE knowledge_document_visible_to ADD CONSTRAINT FK_knowledge_document_visible_to_so
            FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE knowledge_document_visible_to');
        $this->addSql('DROP TABLE knowledge_document_owner');
        $this->addSql('DROP TABLE knowledge_document');
    }
}