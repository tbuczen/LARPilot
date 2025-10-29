# Story AI System - Setup Guide

This guide walks you through setting up the AI-powered story generation features in LARPilot.

## Quick Start (5 minutes)

For development with free/local options:

```bash
# 1. Install Ollama (local AI)
curl -fsSL https://ollama.com/install.sh | sh
ollama pull mixtral:8x7b

# 2. Update environment
cp .env .env.local
echo "AI_PROVIDER=ollama" >> .env.local
echo "OLLAMA_BASE_URL=http://localhost:11434" >> .env.local
echo "OLLAMA_MODEL=mixtral:8x7b" >> .env.local
echo "EMBEDDING_PROVIDER=local" >> .env.local

# 3. Run database migrations
make migrate

# 4. Build context for a LARP
php bin/console app:story-ai:build-context 1
```

Done! AI features are now available in the backoffice.

## Detailed Setup

### Prerequisites

- Docker and Docker Compose (for local development)
- PostgreSQL 16+ (included in docker-compose)
- PHP 8.2+
- Composer

### Step 1: Choose AI Provider

You have three options:

#### Option A: Ollama (Recommended for Development)

**Pros**: Free, local, no API keys, good quality
**Cons**: Requires ~8GB RAM, GPU helpful but not required

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull a model (choose one)
ollama pull mixtral:8x7b    # Best quality, needs 16GB RAM
ollama pull llama2:13b      # Good quality, needs 8GB RAM
ollama pull mistral:7b      # Fast, needs 4GB RAM

# Start Ollama server (in background)
ollama serve
```

Add to `.env.local`:
```env
AI_PROVIDER=ollama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=mixtral:8x7b
EMBEDDING_PROVIDER=local
```

#### Option B: OpenAI (Recommended for Production)

**Pros**: Best quality, reliable, fast
**Cons**: Paid (~$8-10/month for typical LARP usage)

1. Get API key from https://platform.openai.com/api-keys
2. Add to `.env.local`:

```env
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...your-key...
OPENAI_MODEL=gpt-4-turbo-preview
EMBEDDING_PROVIDER=openai
```

#### Option C: Anthropic Claude

**Pros**: Excellent reasoning, very long context (200K tokens)
**Cons**: Paid (~$3-5/month for typical usage)

1. Get API key from https://console.anthropic.com/
2. Add to `.env.local`:

```env
AI_PROVIDER=claude
CLAUDE_API_KEY=sk-ant-...your-key...
CLAUDE_MODEL=claude-3-sonnet-20240229
EMBEDDING_PROVIDER=openai  # Claude doesn't provide embeddings, use OpenAI
```

### Step 2: Set Up Vector Database

#### Option A: PostgreSQL with pgvector (Recommended)

Uses your existing PostgreSQL database. Simplest option.

1. Update `docker-compose.yml`:

```yaml
services:
  db:
    image: pgvector/pgvector:pg16  # Change from regular postgres
    # ... rest of config unchanged
```

2. Restart database:

```bash
docker compose down
docker compose up -d
```

3. Environment variables (already configured if using OpenAI):

```env
EMBEDDING_PROVIDER=openai
OPENAI_API_KEY=sk-...
```

#### Option B: ChromaDB (Advanced)

For better performance with large datasets (1M+ vectors).

1. Add to `docker-compose.yml`:

```yaml
services:
  # ... existing services

  chroma:
    image: ghcr.io/chroma-core/chroma:latest
    ports:
      - "8000:8000"
    volumes:
      - chroma_data:/chroma/chroma
    environment:
      - IS_PERSISTENT=TRUE

volumes:
  # ... existing volumes
  chroma_data:
```

2. Update `.env.local`:

```env
VECTOR_STORE=chroma
CHROMA_BASE_URL=http://localhost:8000
CHROMA_COLLECTION_PREFIX=larpilot_
```

3. Restart Docker:

```bash
docker compose up -d
```

### Step 3: Run Database Migrations

```bash
# Create migration
php bin/console make:migration

# Review the generated migration in migrations/
# It should create larp_embedding table

# Run migration
php bin/console doctrine:migrations:migrate
```

Or using Docker:

```bash
make migrate
```

### Step 4: Build Initial Context

For each LARP in your system, build the vector database context:

```bash
# Build context for LARP with ID 1
php bin/console app:story-ai:build-context 1

# Or multiple LARPs
php bin/console app:story-ai:build-context 1
php bin/console app:story-ai:build-context 2
```

This processes all story objects (characters, threads, quests, events) and generates embeddings.

**Note**: This may take a few minutes for large LARPs. For a typical LARP with 100 characters and 50 threads, expect ~2-3 minutes.

### Step 5: Verify Setup

1. Check that embeddings were created:

```bash
docker compose exec db psql -U larpilot -d larpilot -c "SELECT COUNT(*) FROM larp_embedding WHERE larp_id = 1;"
```

You should see a count > 0.

2. Open your LARP in the backoffice:

```
http://localhost:8000/backoffice/larp/1/ai
```

You should see the AI dashboard with available features.

3. Test a thread suggestion:

- Click "Thread Suggestions"
- Select some characters or tags
- Click "Generate Suggestions"
- You should see AI-generated thread ideas

## Configuration Options

### Environment Variables Reference

```env
# AI Provider (required)
AI_PROVIDER=ollama                    # ollama|openai|claude

# OpenAI Configuration (if using OpenAI)
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo-preview      # gpt-4-turbo-preview|gpt-3.5-turbo
OPENAI_MAX_TOKENS=4096

# Anthropic Claude Configuration (if using Claude)
CLAUDE_API_KEY=sk-ant-...
CLAUDE_MODEL=claude-3-sonnet-20240229 # claude-3-sonnet-20240229|claude-3-opus-20240229
CLAUDE_MAX_TOKENS=4096

# Ollama Configuration (if using Ollama)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=mixtral:8x7b             # mixtral:8x7b|llama2:13b|mistral:7b

# Embedding Provider (required)
EMBEDDING_PROVIDER=openai             # openai|local
# Note: 'local' not yet implemented, use 'openai' for now

# Vector Store (optional, defaults to pgvector)
VECTOR_STORE=pgvector                 # pgvector|chroma|pinecone
CHROMA_BASE_URL=http://localhost:8000 # if using ChromaDB
PINECONE_API_KEY=...                  # if using Pinecone
PINECONE_ENVIRONMENT=us-west1-gcp     # if using Pinecone
PINECONE_INDEX_NAME=larpilot          # if using Pinecone
```

### Service Configuration

Add to `config/services.yaml`:

```yaml
parameters:
    # AI Configuration
    ai_provider: '%env(AI_PROVIDER)%'
    openai_api_key: '%env(OPENAI_API_KEY)%'
    ollama_base_url: '%env(default::OLLAMA_BASE_URL)%'
    embedding_provider: '%env(EMBEDDING_PROVIDER)%'

services:
    # ... existing services

    # AI Provider (configured in Phase 1)
    App\Domain\StoryAI\Service\AIProviderInterface:
        alias: App\Domain\StoryAI\Service\OpenAIProvider  # Change based on provider

    App\Domain\StoryAI\Service\OpenAIProvider:
        arguments:
            $apiKey: '%openai_api_key%'
            $model: '%env(default:gpt-4-turbo-preview:OPENAI_MODEL)%'

    App\Domain\StoryAI\Service\OllamaProvider:
        arguments:
            $baseUrl: '%ollama_base_url%'
            $model: '%env(default:mixtral:8x7b:OLLAMA_MODEL)%'

    App\Domain\StoryAI\Service\EmbeddingService:
        arguments:
            $openaiApiKey: '%openai_api_key%'
            $embeddingProvider: '%embedding_provider%'
```

## Troubleshooting

### Issue: "AI provider not available"

**Cause**: Provider not running or misconfigured

**Solutions**:

For Ollama:
```bash
# Check if Ollama is running
curl http://localhost:11434/api/tags

# If not, start it
ollama serve
```

For OpenAI/Claude:
```bash
# Test API key
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### Issue: "pgvector extension not found"

**Cause**: Wrong PostgreSQL image

**Solution**: Update docker-compose.yml to use `pgvector/pgvector:pg16` image, then:
```bash
docker compose down
docker compose up -d
```

### Issue: "No embeddings found for LARP"

**Cause**: Context not built

**Solution**: Run context builder:
```bash
php bin/console app:story-ai:build-context <larp-id>
```

### Issue: Slow response times

**Causes & Solutions**:

1. **Ollama model too large for system**:
   - Switch to smaller model: `ollama pull mistral:7b`
   - Update `OLLAMA_MODEL=mistral:7b` in `.env.local`

2. **No GPU for Ollama**:
   - Ollama will use CPU, which is slower but works
   - Consider using cloud provider (OpenAI/Claude) instead

3. **Vector search slow**:
   - Ensure index is created:
   ```sql
   CREATE INDEX IF NOT EXISTS idx_larp_embedding_vector
   ON larp_embedding USING ivfflat (embedding vector_cosine_ops);
   ```

### Issue: High API costs (OpenAI/Claude)

**Solutions**:

1. **Monitor usage**:
   ```bash
   # View token usage
   docker compose exec db psql -U larpilot -d larpilot -c \
     "SELECT SUM(tokens_used) FROM ai_generation_request WHERE larp_id = 1;"
   ```

2. **Reduce context size**:
   - Update `ThreadSuggestionService` to use fewer characters in prompts
   - Reduce vector search limit (currently 20, try 10)

3. **Switch to Ollama** for development/testing

4. **Use caching**:
   - Results are cached in `ai_generation_result` table
   - Reuse previous suggestions when possible

## Next Steps

After setup is complete:

1. **Phase 1**: Test basic thread suggestions
   - Navigate to `/backoffice/larp/{id}/ai/threads`
   - Generate suggestions with different filters
   - Verify results are relevant

2. **Phase 2**: Set up automatic context updates
   - Create/edit a character
   - Verify embedding is updated automatically
   - Check: `SELECT * FROM larp_embedding ORDER BY updated_at DESC LIMIT 1;`

3. **Phase 3**: Implement gap analysis feature
   - Follow implementation TODOs in `StoryAIController`
   - Create `GapAnalysisService`

4. **Phase 4**: Implement quest/event suggestions
   - Similar to thread suggestions
   - Add corresponding DTOs and services

5. **Phase 5**: Add advanced features
   - Cost tracking dashboard
   - User ratings/feedback
   - Custom prompt templates

## Production Deployment

### Security Checklist

- [ ] Store API keys in Symfony Secrets, not `.env`
- [ ] Enable rate limiting (max 10 requests/hour per user)
- [ ] Add user consent for AI features in settings
- [ ] Set up monitoring/alerting for API costs
- [ ] Review and sanitize data sent to AI providers
- [ ] Enable audit logging for all AI requests

### Performance Checklist

- [ ] Use Redis cache for frequent queries
- [ ] Set up Symfony Messenger for async context building
- [ ] Configure proper pgvector indexes
- [ ] Monitor token usage and costs
- [ ] Set up CDN for static assets

### Monitoring

Add to your monitoring system:

**Metrics to track**:
- AI requests per hour/day
- Average response time
- Token usage per LARP
- Error rate by provider
- User acceptance rate of suggestions

**Alerts**:
- Error rate > 5%
- Response time > 30 seconds
- Daily cost > $5
- API key expiration warning

## Cost Estimates

Based on typical LARP usage (100 characters, 50 threads, 100 quests):

### OpenAI

**Setup** (one-time per LARP):
- Generate embeddings: $0.002
- Initial context: < $0.01

**Monthly usage** (100 AI requests):
- Thread suggestions: $3-5
- Gap analysis: $2-3
- Quest suggestions: $2-3
- **Total: ~$8-10/month** per active LARP

### Claude

**Monthly usage** (100 AI requests):
- Thread suggestions: $1-2
- Gap analysis: $1-2
- Quest suggestions: $1-2
- **Total: ~$3-5/month** per active LARP

### Ollama (Local)

**Monthly cost**: $0
**One-time cost**: Hardware (if needed):
- 16GB RAM: ~$50-100
- GPU (optional): ~$200-500

## Support and Resources

- **Documentation**: `/docs/technical/STORY_AI_SYSTEM.md`
- **Vector DB Guide**: `/docs/technical/VECTOR_DB_IMPLEMENTATION.md`
- **GitHub Issues**: https://github.com/tbuczen/LARPilot/issues
- **OpenAI Docs**: https://platform.openai.com/docs
- **Ollama Docs**: https://ollama.com/docs
- **pgvector Docs**: https://github.com/pgvector/pgvector

---

**Last Updated**: 2025-10-28
**Tested With**: Symfony 7.2, PHP 8.2, PostgreSQL 16
