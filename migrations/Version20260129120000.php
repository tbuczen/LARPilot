<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add pgvector extension and create StoryAI embedding tables.
 */
final class Version20260129120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable pgvector extension and create StoryAI embedding tables';
    }

    public function up(Schema $schema): void
    {
        // Enable pgvector extension
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');

        // Create story_object_embedding table
        $this->addSql('
            CREATE TABLE story_object_embedding (
                id UUID NOT NULL,
                larp_id UUID NOT NULL,
                story_object_id UUID NOT NULL,
                serialized_content TEXT NOT NULL,
                content_hash VARCHAR(64) NOT NULL,
                embedding vector(1536) NOT NULL,
                embedding_model VARCHAR(100) NOT NULL,
                dimensions INT NOT NULL,
                token_count INT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('CREATE INDEX idx_embedding_larp ON story_object_embedding (larp_id)');
        $this->addSql('CREATE INDEX idx_embedding_story_object ON story_object_embedding (story_object_id)');
        $this->addSql('CREATE INDEX idx_embedding_content_hash ON story_object_embedding (content_hash)');

        // Create HNSW index for fast approximate nearest neighbor search
        $this->addSql('CREATE INDEX idx_embedding_vector ON story_object_embedding USING hnsw (embedding vector_cosine_ops)');

        $this->addSql('ALTER TABLE story_object_embedding ADD CONSTRAINT FK_embedding_larp FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE story_object_embedding ADD CONSTRAINT FK_embedding_story_object FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create larp_lore_document table
        $this->addSql('
            CREATE TABLE larp_lore_document (
                id UUID NOT NULL,
                larp_id UUID NOT NULL,
                created_by_id UUID DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                type VARCHAR(50) NOT NULL,
                content TEXT NOT NULL,
                priority INT NOT NULL,
                always_include BOOLEAN NOT NULL,
                active BOOLEAN NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('CREATE INDEX idx_lore_doc_larp ON larp_lore_document (larp_id)');
        $this->addSql('CREATE INDEX idx_lore_doc_priority ON larp_lore_document (priority)');
        $this->addSql('CREATE INDEX idx_lore_doc_type ON larp_lore_document (type)');

        $this->addSql('ALTER TABLE larp_lore_document ADD CONSTRAINT FK_lore_doc_larp FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_lore_document ADD CONSTRAINT FK_lore_doc_created_by FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create lore_document_chunk table
        $this->addSql('
            CREATE TABLE lore_document_chunk (
                id UUID NOT NULL,
                document_id UUID NOT NULL,
                larp_id UUID NOT NULL,
                content TEXT NOT NULL,
                chunk_index INT NOT NULL,
                content_hash VARCHAR(64) NOT NULL,
                embedding vector(1536) NOT NULL,
                embedding_model VARCHAR(100) NOT NULL,
                dimensions INT NOT NULL,
                token_count INT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('CREATE INDEX idx_chunk_document ON lore_document_chunk (document_id)');
        $this->addSql('CREATE INDEX idx_chunk_larp ON lore_document_chunk (larp_id)');
        $this->addSql('CREATE INDEX idx_chunk_index ON lore_document_chunk (chunk_index)');

        // Create HNSW index for fast approximate nearest neighbor search on chunks
        $this->addSql('CREATE INDEX idx_chunk_vector ON lore_document_chunk USING hnsw (embedding vector_cosine_ops)');

        $this->addSql('ALTER TABLE lore_document_chunk ADD CONSTRAINT FK_chunk_document FOREIGN KEY (document_id) REFERENCES larp_lore_document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lore_document_chunk ADD CONSTRAINT FK_chunk_larp FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lore_document_chunk DROP CONSTRAINT FK_chunk_document');
        $this->addSql('ALTER TABLE lore_document_chunk DROP CONSTRAINT FK_chunk_larp');
        $this->addSql('DROP TABLE lore_document_chunk');

        $this->addSql('ALTER TABLE larp_lore_document DROP CONSTRAINT FK_lore_doc_larp');
        $this->addSql('ALTER TABLE larp_lore_document DROP CONSTRAINT FK_lore_doc_created_by');
        $this->addSql('DROP TABLE larp_lore_document');

        $this->addSql('ALTER TABLE story_object_embedding DROP CONSTRAINT FK_embedding_larp');
        $this->addSql('ALTER TABLE story_object_embedding DROP CONSTRAINT FK_embedding_story_object');
        $this->addSql('DROP TABLE story_object_embedding');

        // Note: We don't drop the pgvector extension as it might be used by other tables
    }
}
