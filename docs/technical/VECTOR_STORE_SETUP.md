# Vector Store Setup Guide

LARPilot uses an external vector database for AI-powered semantic search (RAG). This CQRS architecture separates the write side (main PostgreSQL on your hosting) from the read side (external vector store with pgvector).

## Why External Vector Store?

Many shared hosting providers (like Cyberfolks) don't support PostgreSQL extensions like pgvector. By using an external vector store:

- **No hosting limitations**: Works with any PostgreSQL hosting
- **Free tier available**: Supabase offers 500MB free
- **Scalable**: Can upgrade independently of main database
- **CQRS benefits**: Read-optimized for semantic search

## Supported Providers

### Supabase (Recommended)

**Free Tier**: 500MB database, 2 projects, pgvector included

1. **Create Account**: Go to [supabase.com](https://supabase.com) and sign up
2. **Create Project**:
   - Choose a region close to your server (EU for Poland-based hosting)
   - Note your project reference (e.g., `abc123def`)
3. **Get Service Key**:
   - Go to Project Settings > API
   - Copy the `service_role` key (NOT the `anon` key)

### Database Setup

Run this SQL in Supabase SQL Editor (Database > SQL Editor):

```sql
-- Enable pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;

-- Create embeddings table
CREATE TABLE larpilot_embeddings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    entity_id UUID NOT NULL,
    larp_id UUID NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    type VARCHAR(20) NOT NULL,
    title TEXT NOT NULL,
    serialized_content TEXT NOT NULL,
    content_hash VARCHAR(64) NOT NULL,
    embedding vector(1536) NOT NULL,
    embedding_model VARCHAR(100) DEFAULT 'text-embedding-3-small',
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for filtering
CREATE INDEX idx_embeddings_larp ON larpilot_embeddings(larp_id);
CREATE INDEX idx_embeddings_entity ON larpilot_embeddings(entity_id);
CREATE INDEX idx_embeddings_type ON larpilot_embeddings(type);
CREATE INDEX idx_embeddings_entity_type ON larpilot_embeddings(entity_type);
CREATE INDEX idx_embeddings_hash ON larpilot_embeddings(content_hash);

-- Create vector similarity index (IVFFlat for performance)
-- Note: lists=100 is good for up to ~100k vectors; adjust for larger datasets
CREATE INDEX idx_embeddings_vector ON larpilot_embeddings
    USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);

-- Create unique constraint for upsert logic
CREATE UNIQUE INDEX idx_embeddings_entity_unique ON larpilot_embeddings(entity_id);

-- Create RPC function for similarity search
CREATE OR REPLACE FUNCTION search_embeddings(
    query_embedding vector(1536),
    larp_id_filter UUID,
    match_threshold FLOAT DEFAULT 0.5,
    match_count INT DEFAULT 10,
    type_filter VARCHAR DEFAULT NULL,
    entity_type_filter VARCHAR DEFAULT NULL
)
RETURNS TABLE (
    entity_id UUID,
    larp_id UUID,
    entity_type VARCHAR,
    type VARCHAR,
    title TEXT,
    serialized_content TEXT,
    similarity FLOAT,
    metadata JSONB
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.entity_id,
        e.larp_id,
        e.entity_type,
        e.type,
        e.title,
        e.serialized_content,
        1 - (e.embedding <=> query_embedding) AS similarity,
        e.metadata
    FROM larpilot_embeddings e
    WHERE e.larp_id = larp_id_filter
      AND 1 - (e.embedding <=> query_embedding) >= match_threshold
      AND (type_filter IS NULL OR e.type = type_filter)
      AND (entity_type_filter IS NULL OR e.entity_type = entity_type_filter)
    ORDER BY e.embedding <=> query_embedding
    LIMIT match_count;
END;
$$;

-- Grant permissions to the API
GRANT EXECUTE ON FUNCTION search_embeddings TO anon, authenticated, service_role;
GRANT ALL ON larpilot_embeddings TO anon, authenticated, service_role;

-- Create updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_timestamp
    BEFORE UPDATE ON larpilot_embeddings
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at();
```

## Configuration

Add to your `.env.local`:

```bash
# Format: supabase://SERVICE_KEY@PROJECT_REF
VECTOR_STORE_DSN=supabase://eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...@abc123def
```

Where:
- `SERVICE_KEY` is your Supabase service_role key
- `PROJECT_REF` is your project reference (from the project URL)

## Verifying Setup

1. **Check Symfony config**:
   ```bash
   php bin/console debug:container VectorStoreInterface
   ```

2. **Test connection** (create a simple test command or use the existing reindex command):
   ```bash
   php bin/console app:story-ai:reindex --larp=<larp-id>
   ```

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        WRITE SIDE                               │
│                   (Cyberfolks PostgreSQL)                       │
│                                                                 │
│  StoryObject ──▶ Doctrine Event ──▶ Messenger ──▶ Handler      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        READ SIDE                                │
│                  (Supabase + pgvector)                          │
│                                                                 │
│  EmbeddingService ──▶ VectorStoreInterface ──▶ Supabase API    │
│                                                                 │
│  VectorSearchService ◀── search_embeddings() RPC function      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Cost Considerations

### Supabase Free Tier Limits
- 500 MB database storage
- 2 GB bandwidth per month
- Unlimited API requests
- 2 projects

### Estimated Usage
- Each embedding: ~6KB (1536 dimensions × 4 bytes)
- With metadata: ~10KB per document
- 500MB ≈ 50,000 embeddings

For beta testing, this should be more than sufficient. Upgrade to paid plan ($25/month) when you exceed these limits.

## Migrating Existing Data

If you have existing embeddings in your local database:

1. Export from local:
   ```sql
   SELECT entity_id, larp_id, entity_type, serialized_content, embedding
   FROM story_object_embedding;
   ```

2. Run reindex command to populate Supabase:
   ```bash
   php bin/console app:story-ai:reindex --all
   ```

## Troubleshooting

### "Function search_embeddings does not exist"
Run the SQL setup script again - the function may not have been created.

### "Permission denied"
Ensure you're using the `service_role` key, not the `anon` key.

### Slow searches
- Check if the IVFFlat index was created
- For large datasets (>100k vectors), recreate with more lists:
  ```sql
  DROP INDEX idx_embeddings_vector;
  CREATE INDEX idx_embeddings_vector ON larpilot_embeddings
      USING ivfflat (embedding vector_cosine_ops) WITH (lists = 500);
  ```

### Connection timeouts
- Check if your hosting allows outbound HTTPS connections
- Verify the Supabase URL is correct
