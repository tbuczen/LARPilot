# StoryAI Domain

AI-powered assistant for LARP story management using RAG (Retrieval-Augmented Generation).

## Overview

StoryAI provides intelligent querying and analysis of LARP story content by:
1. **Indexing** story objects and lore documents into vector embeddings
2. **Searching** content using semantic similarity
3. **Generating** AI responses with relevant context

## Architecture

```
src/Domain/StoryAI/
├── Entity/
│   ├── StoryObjectEmbedding.php    # Vector embedding for story objects
│   ├── LarpLoreDocument.php        # Custom lore/setting documents
│   └── LoreDocumentChunk.php       # Chunked document for embeddings
├── Service/
│   ├── Embedding/
│   │   ├── EmbeddingService.php    # Indexing logic
│   │   └── StoryObjectSerializer.php
│   ├── Query/
│   │   ├── RAGQueryService.php     # Main query service
│   │   ├── VectorSearchService.php # Similarity search
│   │   └── ContextBuilder.php      # Context assembly
│   └── Provider/
│       ├── OpenAIProvider.php      # LLM/embedding provider
│       ├── LLMProviderInterface.php
│       └── EmbeddingProviderInterface.php
├── Controller/API/
│   └── AIAssistantController.php   # REST API endpoints
├── Command/
│   └── ReindexStoryAICommand.php   # CLI reindexing
└── Message/ & MessageHandler/      # Async indexing via Messenger
```

## API Endpoints

All endpoints are under `/api/larp/{larp}/ai/`:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/query` | POST | Ask questions about the story |
| `/search` | POST | Semantic search for content |
| `/suggest-story-arc` | POST | Get story arc suggestions for a character |
| `/suggest-relationships` | POST | Get relationship suggestions for a character |
| `/find-connections` | POST | Find connections between two story objects |
| `/analyze-consistency` | POST | Check plot consistency |

### Example: Query

```bash
curl -X POST /api/larp/123/ai/query \
  -H "Content-Type: application/json" \
  -d '{"query": "What is the history of the Northern Kingdom?"}'
```

Response:
```json
{
  "answer": "The Northern Kingdom was founded in...",
  "sources": [
    {"type": "character", "id": 1, "title": "King Aldric"},
    {"type": "lore_document", "id": 5, "title": "World History"}
  ]
}
```

## Indexing

### Automatic Indexing

Story objects are automatically indexed when created/updated via `StoryObjectIndexSubscriber`.

### Manual Reindex

```bash
# Reindex all LARPs (async via Messenger)
php bin/console app:story-ai:reindex

# Reindex specific LARP synchronously
php bin/console app:story-ai:reindex --larp=123 --sync
```

## Lore Documents

Upload custom setting/lore content that AI uses for context.

**Document Types:**
- Setting Overview
- World History
- Magic Rules
- Culture Notes
- Geography
- Politics
- Religion
- Economics
- General

Documents are chunked (500 chars, 100 overlap) and embedded for retrieval.

## Setup Guide

### Prerequisites

- PostgreSQL 15+ with **pgvector** extension
- OpenAI API key
- Symfony Messenger configured (for async indexing)

---

### Step 1: Install pgvector Extension

**Production (managed PostgreSQL):**

Most managed PostgreSQL services (AWS RDS, Supabase, Neon) support pgvector. Enable it via their dashboard or run:

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

**Local/Docker:**

The project's Docker setup includes pgvector. If using a custom setup:

```bash
# Ubuntu/Debian
sudo apt install postgresql-15-pgvector

# Or build from source
git clone https://github.com/pgvector/pgvector.git
cd pgvector && make && sudo make install
```

---

### Step 2: Configure Environment Variables

Add to `.env.local` (local) or your production secrets:

```env
# Required: OpenAI API key
OPENAI_API_KEY=sk-your-api-key-here

# Optional: Model configuration (defaults shown)
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_COMPLETION_MODEL=gpt-4o-mini
```

**Model Options:**

| Model | Use Case | Cost |
|-------|----------|------|
| `text-embedding-3-small` | Embeddings (default) | Low |
| `text-embedding-3-large` | Higher quality embeddings | Medium |
| `gpt-4o-mini` | Completions (default) | Low |
| `gpt-4o` | Higher quality responses | High |

---

### Step 3: Run Database Migrations

```bash
# Local (Docker)
make migrate

# Production
php bin/console doctrine:migrations:migrate --no-interaction
```

This creates:
- `story_object_embedding` - Vector embeddings for story objects
- `larp_lore_document` - Lore document metadata
- `lore_document_chunk` - Chunked document embeddings with HNSW index

---

### Step 4: Configure Message Queue

StoryAI uses Symfony Messenger for async indexing. The routing is pre-configured in `config/packages/messenger.yaml`.

**Local Development:**

Use Doctrine transport (default in `.env`):

```env
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

Run the worker:

```bash
# In a separate terminal
docker compose exec php php bin/console messenger:consume async -vv
```

**Production:**

Use a dedicated message broker:

```env
# Redis
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

Run workers via supervisor:

```ini
[program:messenger-worker]
command=php /var/www/bin/console messenger:consume async --time-limit=3600
numprocs=2
autostart=true
autorestart=true
```

---

### Step 5: Initial Indexing

Index existing story objects for a LARP:

```bash
# Async (recommended for large LARPs)
php bin/console app:story-ai:reindex --larp=<LARP_ID>

# Sync (for testing/small datasets)
php bin/console app:story-ai:reindex --larp=<LARP_ID> --sync

# Reindex all LARPs
php bin/console app:story-ai:reindex
```

---

### Step 6: Verify Setup

Test the API:

```bash
curl -X POST http://localhost/api/larp/<LARP_ID>/ai/search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"query": "test"}'
```

---

### Local/Test Environment Notes

**Test environment** (`.env.test`):

```env
# Use sync transport for tests (no worker needed)
MESSENGER_TRANSPORT_DSN=sync://

# Use test API key or mock
OPENAI_API_KEY=test-key
```

**Disable AI in tests:**

For unit/functional tests that don't need AI, mock the services:

```php
$ragQueryService = $this->createMock(RAGQueryService::class);
$ragQueryService->method('query')->willReturn(new AIQueryResult('Mock answer', []));
```

**Cost considerations:**

- Embedding calls: ~$0.02 per 1M tokens (text-embedding-3-small)
- Completion calls: ~$0.15 per 1M input tokens (gpt-4o-mini)
- Use `--sync` flag sparingly in development to control costs

---

## Configuration Reference

Full environment variables:

```env
# Required
OPENAI_API_KEY=sk-...

# Optional (with defaults)
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_COMPLETION_MODEL=gpt-4o-mini

# Message queue
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

## Key Services

### RAGQueryService

Main entry point for AI queries:

```php
$result = $ragQueryService->query($larp, "Who are the main antagonists?");
// Returns AIQueryResult with answer and sources
```

### EmbeddingService

Handles indexing:

```php
$embeddingService->indexStoryObject($character);
$embeddingService->indexLoreDocument($document);
$embeddingService->reindexLarp($larp);
```

### VectorSearchService

Performs similarity search:

```php
$results = $vectorSearchService->search($larp, $queryEmbedding, limit: 10);
// Returns SearchResult[] with scores
```
