# StoryAI Domain

AI-powered assistant for LARP story management using RAG (Retrieval-Augmented Generation).

## Overview

StoryAI provides intelligent querying and analysis of LARP story content by:
1. **Indexing** story objects into vector embeddings (stored in Supabase)
2. **Searching** content using semantic similarity
3. **Generating** AI responses with relevant context

## Architecture

```
┌─────────────────────────┐              ┌─────────────────────────┐
│    Main PostgreSQL      │              │        Supabase         │
├─────────────────────────┤              ├─────────────────────────┤
│ • LARPs                 │              │ larpilot_embeddings     │
│ • StoryObjects          │──── index ──▶│ ├─ entity_id            │
│ • Users                 │              │ ├─ larp_id              │
│ • Participants          │              │ ├─ embedding (vector)   │
│ • ...                   │              │ ├─ serialized_content   │
└─────────────────────────┘              │ ├─ content_hash         │
                                         │ └─ metadata             │
                                         └─────────────────────────┘
```

**Key principle:** All AI/embedding data lives in Supabase. The main application database has no knowledge of AI features.

### Directory Structure

```
src/Domain/StoryAI/
├── DTO/
│   ├── VectorDocument.php        # Document for vector store
│   ├── VectorSearchResult.php    # Search result from vector store
│   ├── SearchResult.php          # Unified search result
│   └── AIQueryResult.php         # RAG query response
├── Service/
│   ├── Embedding/
│   │   ├── EmbeddingService.php       # Indexing logic
│   │   └── StoryObjectSerializer.php  # Converts StoryObjects to text
│   ├── Query/
│   │   ├── RAGQueryService.php        # Main query service
│   │   ├── VectorSearchService.php    # Similarity search
│   │   └── ContextBuilder.php         # Context assembly for LLM
│   ├── VectorStore/
│   │   ├── VectorStoreInterface.php   # Vector store abstraction
│   │   ├── SupabaseVectorStore.php    # Supabase implementation
│   │   ├── NullVectorStore.php        # No-op for testing/disabled
│   │   └── VectorStoreFactory.php     # Creates appropriate store
│   └── Provider/
│       ├── OpenAIProvider.php             # LLM/embedding provider
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
curl -X POST /api/larp/{larp-uuid}/ai/query \
  -H "Content-Type: application/json" \
  -d '{"query": "What is the history of the Northern Kingdom?"}'
```

Response:
```json
{
  "answer": "The Northern Kingdom was founded in...",
  "sources": [
    {"type": "Character", "id": "uuid", "title": "King Aldric"},
    {"type": "Thread", "id": "uuid", "title": "The Northern Wars"}
  ]
}
```

## Indexing

### Automatic Indexing

Story objects are automatically indexed when created/updated via `StoryObjectIndexSubscriber`.

### Manual Reindex

```bash
# Reindex a specific LARP (synchronous)
php bin/console app:story-ai:reindex <LARP-UUID>

# Force reindex (even if content unchanged)
php bin/console app:story-ai:reindex <LARP-UUID> --force

# Async via Messenger
php bin/console app:story-ai:reindex <LARP-UUID> --async
```

## Setup Guide

### Prerequisites

- OpenAI API key
- Supabase project with pgvector extension
- Symfony Messenger configured (for async indexing)

---

### Step 1: Set Up Supabase

See [VECTOR_STORE_SETUP.md](VECTOR_STORE_SETUP.md) for detailed instructions on:
- Creating the `larpilot_embeddings` table
- Setting up the `search_embeddings` RPC function
- Configuring pgvector indexes

---

### Step 2: Configure Environment Variables

Add to `.env.local`:

```env
# OpenAI
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_COMPLETION_MODEL=gpt-4o-mini

# Supabase Vector Store
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_SERVICE_KEY=your-service-role-key

# Vector store provider (supabase or null)
VECTOR_STORE_PROVIDER=supabase
```

---

### Step 3: Configure Message Queue

StoryAI uses Symfony Messenger for async indexing.

**Local Development:**

```env
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

Run the worker:

```bash
docker compose exec php php bin/console messenger:consume async -vv
```

**Production:**

```env
# Redis
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
```

---

### Step 4: Initial Indexing

Index existing story objects:

```bash
# Get your LARP UUID
php bin/console doctrine:query:sql "SELECT id, title FROM larp LIMIT 5"

# Index the LARP
php bin/console app:story-ai:reindex <LARP-UUID>
```

---

### Step 5: Verify Setup

```bash
curl -X POST http://localhost/api/larp/<LARP-UUID>/ai/search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"query": "test"}'
```

---

## Key Services

### EmbeddingService

Handles indexing of story objects:

```php
// Index a single story object
$embeddingService->indexStoryObject($character);

// Reindex all story objects in a LARP
$stats = $embeddingService->reindexLarp($larp);
// Returns: ['indexed' => 42, 'skipped' => 10, 'errors' => 0]

// Delete embedding when story object is deleted
$embeddingService->deleteStoryObjectEmbedding($character);

// Generate embedding for a query
$vector = $embeddingService->generateQueryEmbedding("Who is the king?");
```

### VectorSearchService

Performs similarity search:

```php
$results = $vectorSearchService->search(
    larp: $larp,
    query: "characters involved in the rebellion",
    limit: 10,
    minSimilarity: 0.5
);
// Returns SearchResult[] with similarity scores
```

### RAGQueryService

Main entry point for AI queries:

```php
$result = $ragQueryService->query($larp, "Who are the main antagonists?");
// Returns AIQueryResult with answer and sources
```

### ContextBuilder

Assembles context for LLM prompts:

```php
$context = $contextBuilder->buildContext($searchResults, $larp, maxTokens: 12000);
$systemPrompt = $contextBuilder->buildSystemPrompt($larp);
```

---

## Cost Considerations

| Operation | Model | Cost (approx) |
|-----------|-------|---------------|
| Embedding | text-embedding-3-small | $0.02 / 1M tokens |
| Embedding | text-embedding-3-large | $0.13 / 1M tokens |
| Completion | gpt-4o-mini | $0.15 / 1M input tokens |
| Completion | gpt-4o | $2.50 / 1M input tokens |

**Tips:**
- Use `--force` flag sparingly (avoids unnecessary re-embeddings)
- Content hash comparison prevents redundant API calls
- Supabase free tier: 500MB database, sufficient for most LARPs

---

## Future: Lore Documents

Custom lore/setting documents (world history, magic rules, etc.) can be added later by:
1. Uploading text content via a simple form
2. Chunking the content (the chunking logic exists in git history)
3. Storing chunks directly in Supabase as `type: 'lore_chunk'`

No additional database tables needed - the `larpilot_embeddings` table handles both story objects and lore chunks via the `type` field.
