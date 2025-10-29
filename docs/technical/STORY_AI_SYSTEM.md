# Story AI System - Technical Documentation

## Overview

The Story AI System provides AI-powered story generation capabilities for LARP organizers, including:
- **History Gap Analysis**: Identify gaps in character backstories and timelines
- **Thread/Quest/Event Suggestions**: Generate new story elements based on LARP context
- **Missing Connections**: Detect and suggest missing relationships between story objects
- **Tag-Based Generation**: Create content based on character/quest tags
- **Consistency Checking**: Validate story coherence and timeline consistency

**Issue Reference**: [#59](https://github.com/tbuczen/LARPilot/issues/59)

## Architecture

### Domain Structure

```
src/Domain/StoryAI/
├── Entity/
│   ├── AIGenerationRequest.php      # Tracks AI generation requests
│   ├── AIGenerationResult.php       # Stores generated content
│   └── LarpContextSnapshot.php      # Versioned LARP context for vector DB
├── Repository/
│   ├── AIGenerationRequestRepository.php
│   ├── AIGenerationResultRepository.php
│   └── LarpContextSnapshotRepository.php
├── Service/
│   ├── AIProviderInterface.php      # Common interface for AI providers
│   ├── OpenAIProvider.php           # OpenAI implementation
│   ├── ClaudeProvider.php           # Anthropic Claude implementation
│   ├── LocalProvider.php            # Local/free AI models (Ollama, etc.)
│   ├── VectorStoreInterface.php     # Vector DB abstraction
│   ├── ChromaDBStore.php            # ChromaDB implementation (free, open-source)
│   ├── PineconeStore.php            # Pinecone implementation (cloud option)
│   ├── ContextBuilder.php           # Builds LARP context for AI
│   ├── PromptBuilder.php            # Constructs AI prompts
│   ├── StoryGapAnalyzer.php         # Analyzes gaps in story
│   ├── ThreadSuggestionService.php  # Generates thread suggestions
│   ├── QuestSuggestionService.php   # Generates quest suggestions
│   └── EventSuggestionService.php   # Generates event suggestions
├── DTO/
│   ├── AIGenerationRequestDTO.php
│   ├── GapAnalysisResultDTO.php
│   ├── ThreadSuggestionDTO.php
│   ├── QuestSuggestionDTO.php
│   └── EventSuggestionDTO.php
├── Controller/
│   ├── Backoffice/
│   │   ├── StoryAIController.php    # Main AI features UI
│   │   ├── GapAnalysisController.php
│   │   └── SuggestionController.php
│   └── API/
│       └── StoryAIApiController.php # AJAX endpoints
└── Form/
    ├── Type/
    │   ├── AIGenerationConfigType.php
    │   └── SuggestionFilterType.php
    └── Filter/
        └── GenerationHistoryFilterType.php
```

### Template Structure

```
templates/backoffice/story_ai/
├── dashboard.html.twig              # Main AI dashboard
├── gap_analysis/
│   ├── index.html.twig
│   └── results.html.twig
├── suggestions/
│   ├── threads.html.twig
│   ├── quests.html.twig
│   └── events.html.twig
└── history/
    └── list.html.twig               # Generation history
```

## AI Provider Options

### 1. OpenAI (Recommended for Production)

**Pros**:
- Excellent quality (GPT-4)
- Good API stability
- Function calling support
- Structured output

**Cons**:
- Paid service ($0.03/1K tokens for GPT-4)
- Requires API key
- Data sent to third party

**Environment Variables**:
```env
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo-preview
OPENAI_MAX_TOKENS=4096
```

### 2. Anthropic Claude (Alternative)

**Pros**:
- Excellent reasoning capabilities
- Long context window (200K tokens)
- Strong creative writing
- Good at maintaining consistency

**Cons**:
- Paid service ($0.015/1K tokens for Claude 3 Sonnet)
- Requires API key

**Environment Variables**:
```env
CLAUDE_API_KEY=sk-ant-...
CLAUDE_MODEL=claude-3-sonnet-20240229
CLAUDE_MAX_TOKENS=4096
```

### 3. Local/Free Options

#### Option A: Ollama (Local)

**Pros**:
- Completely free
- No API keys needed
- Data stays local
- No per-request costs

**Cons**:
- Requires local GPU/CPU resources
- Lower quality than commercial models
- Self-hosted infrastructure

**Recommended Models**:
- `mixtral:8x7b` - Good balance of quality/speed
- `llama2:13b` - Faster, lower quality
- `mistral:7b` - Fastest, basic quality

**Setup**:
```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull model
ollama pull mixtral:8x7b

# Run server
ollama serve
```

**Environment Variables**:
```env
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=mixtral:8x7b
```

#### Option B: HuggingFace Inference API (Free Tier)

**Pros**:
- Free tier available
- No local infrastructure
- Multiple model options

**Cons**:
- Rate limited on free tier
- Lower quality than GPT-4
- API stability varies

**Environment Variables**:
```env
HUGGINGFACE_API_KEY=hf_...
HUGGINGFACE_MODEL=mistralai/Mixtral-8x7B-Instruct-v0.1
```

### Recommended Configuration

For development: **Ollama** (free, local)
For production: **OpenAI GPT-4** or **Claude 3 Sonnet** (best quality)

## Vector Database Strategy

### Purpose

Vector databases enable semantic search over LARP content:
- Store embeddings of all story objects (characters, threads, quests, events)
- Query relevant context based on semantic similarity
- Keep AI context focused and relevant
- Enable "find similar" features

### Vector DB Options

#### Option 1: ChromaDB (Recommended for Start)

**Pros**:
- Open-source and free
- Easy to self-host
- Python/REST API
- Embedded mode available
- Persistent storage

**Cons**:
- Single-machine deployment
- Limited scale compared to cloud options

**Setup**:
```bash
# Install ChromaDB
pip install chromadb

# Run server
chroma run --path /var/lib/chroma
```

**Docker Compose Addition**:
```yaml
chroma:
  image: ghcr.io/chroma-core/chroma:latest
  ports:
    - "8000:8000"
  volumes:
    - chroma_data:/chroma/chroma
  environment:
    - IS_PERSISTENT=TRUE
```

**Environment Variables**:
```env
CHROMA_BASE_URL=http://localhost:8000
CHROMA_COLLECTION_PREFIX=larpilot_
```

#### Option 2: Pinecone (Cloud Option)

**Pros**:
- Managed service
- Highly scalable
- Free tier (1M vectors, 1 index)
- Global low-latency

**Cons**:
- Paid beyond free tier
- Data in third-party cloud

**Environment Variables**:
```env
PINECONE_API_KEY=...
PINECONE_ENVIRONMENT=us-west1-gcp
PINECONE_INDEX_NAME=larpilot
```

#### Option 3: PostgreSQL with pgvector (Simplest)

**Pros**:
- Uses existing PostgreSQL DB
- No additional infrastructure
- Free and open-source
- ACID guarantees

**Cons**:
- Slower than specialized vector DBs
- Limited to ~1M vectors
- Less advanced indexing

**Setup**:
```sql
CREATE EXTENSION vector;

CREATE TABLE larp_embeddings (
    id SERIAL PRIMARY KEY,
    larp_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    content TEXT NOT NULL,
    embedding vector(1536), -- OpenAI ada-002 dimension
    created_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (larp_id) REFERENCES larp(id) ON DELETE CASCADE
);

CREATE INDEX ON larp_embeddings USING ivfflat (embedding vector_cosine_ops);
```

**Environment Variables**:
```env
VECTOR_STORE_DRIVER=pgvector
```

### Recommended Configuration

**Development/MVP**: PostgreSQL with pgvector (simplest)
**Production**: ChromaDB (self-hosted) or Pinecone (managed)

## Data Flow

### 1. Context Building

```
LARP Selection
    ↓
[ContextBuilder]
    ↓
Query Story Objects (Characters, Threads, Quests, Events, Factions, etc.)
    ↓
Filter by tags/filters (if specified)
    ↓
Generate embeddings for each object
    ↓
Store in Vector DB
    ↓
Create LarpContextSnapshot (version tracking)
```

### 2. AI Generation Request

```
User Selects Feature (e.g., "Find History Gaps")
    ↓
[Controller] → Create AIGenerationRequest entity
    ↓
[Service] → Build context from Vector DB
    ↓
Query semantically relevant objects
    ↓
[PromptBuilder] → Construct prompt
    ↓
[AIProvider] → Send to AI API
    ↓
Receive structured response
    ↓
[Service] → Parse and validate
    ↓
Create AIGenerationResult entity
    ↓
Display to user
```

### 3. Vector DB Update Strategy

**Incremental Updates** (per LARP):
```
Entity Change Event (Character created/updated)
    ↓
Generate new embedding
    ↓
Update/Insert in Vector DB
    ↓
Update LarpContextSnapshot.updated_at
```

**Full Rebuild** (manual trigger):
```
Admin triggers "Rebuild Context"
    ↓
Delete all embeddings for LARP
    ↓
Re-process all story objects
    ↓
Create new LarpContextSnapshot
```

## Implementation Phases

### Phase 1: Foundation (MVP)

**Goal**: Basic AI integration with one provider and simple context

**Tasks**:
1. Create StoryAI domain structure
2. Implement AIProviderInterface
3. Implement one provider (OpenAI or Ollama)
4. Create AIGenerationRequest/Result entities
5. Basic ContextBuilder (no vector DB yet)
6. Simple UI for testing

**Deliverables**:
- Working AI provider integration
- Basic request/response tracking
- Simple text-based context building

### Phase 2: Vector Database Integration

**Goal**: Semantic search and efficient context retrieval

**Tasks**:
1. Set up vector DB (pgvector or ChromaDB)
2. Implement VectorStoreInterface
3. Create embedding generation service
4. Implement LarpContextSnapshot entity
5. Build context update workflows
6. Add vector search to context builder

**Deliverables**:
- Working vector DB integration
- Automatic context updates on entity changes
- Semantic search capabilities

### Phase 3: Story Analysis Features

**Goal**: Implement core story analysis features

**Tasks**:
1. StoryGapAnalyzer service
   - Timeline gap detection
   - Character history gaps
   - Missing relationship detection
2. Gap analysis UI
3. Gap analysis results display
4. Export gap analysis reports

**Deliverables**:
- Gap analysis feature
- User-friendly results presentation
- Actionable insights

### Phase 4: Content Suggestion Features

**Goal**: AI-powered content generation

**Tasks**:
1. ThreadSuggestionService
2. QuestSuggestionService
3. EventSuggestionService
4. Suggestion filtering and ranking
5. "Accept Suggestion" workflow
6. Suggestion history tracking

**Deliverables**:
- Thread/Quest/Event suggestion features
- One-click suggestion acceptance
- Suggestion quality tracking

### Phase 5: Advanced Features

**Goal**: Polish and advanced capabilities

**Tasks**:
1. Multiple AI provider support
2. Provider selection UI
3. Cost tracking per LARP
4. Batch generation
5. Custom prompt templates
6. Feedback loop (user ratings)
7. Fine-tuning data collection

**Deliverables**:
- Multi-provider support
- Cost management
- Quality improvement loop

## Embedding Strategy

### What to Embed

For each LARP, create embeddings for:

**Characters**:
```json
{
  "type": "character",
  "id": 123,
  "larp_id": 1,
  "content": "Title: John Smith\nDescription: A brave knight...\nBackstory: Born in...\nTags: warrior, noble, human\nSkills: Swordsmanship, Leadership\nRelationships: Friend of Jane Doe, Enemy of Lord Dark"
}
```

**Threads**:
```json
{
  "type": "thread",
  "id": 456,
  "larp_id": 1,
  "content": "Title: The Lost Artifact\nSummary: A mysterious artifact...\nActs: Act 1: Discovery...\nConnected Characters: John Smith, Jane Doe\nTags: mystery, magic"
}
```

**Quests**:
```json
{
  "type": "quest",
  "id": 789,
  "larp_id": 1,
  "content": "Title: Find the Key\nDescription: Locate the ancient key...\nObjectives: Search castle, Interrogate guards\nRewards: 100 gold\nTags: exploration, puzzle"
}
```

**Events**:
```json
{
  "type": "event",
  "id": 321,
  "larp_id": 1,
  "content": "Title: The Great Battle\nDescription: War breaks out...\nDate: 2024-05-15\nLocation: Castle Gates\nParticipants: John Smith, Army of Light\nTags: combat, major"
}
```

### Embedding Models

**OpenAI**:
- Model: `text-embedding-3-small` (1536 dimensions)
- Cost: $0.00002/1K tokens
- Quality: Excellent

**Free Alternative (via HuggingFace)**:
- Model: `sentence-transformers/all-MiniLM-L6-v2` (384 dimensions)
- Cost: Free
- Quality: Good for most use cases

### Context Window Management

**Token Limits**:
- GPT-4 Turbo: 128K tokens
- Claude 3 Sonnet: 200K tokens
- Mixtral 8x7B: 32K tokens

**Strategy**:
1. Use vector search to find top 20 most relevant objects
2. Calculate total token count
3. If over limit, truncate least relevant objects
4. Always include user-selected objects in context

## Prompt Engineering

### Prompt Templates

Located in: `src/Domain/StoryAI/Prompt/`

**Gap Analysis Prompt**:
```
You are a creative LARP story consultant analyzing "{LARP_TITLE}".

CONTEXT:
{CHARACTERS}
{THREADS}
{QUESTS}
{EVENTS}

TASK: Identify gaps in the story:
1. Timeline gaps (events without clear connections)
2. Character history gaps (incomplete backstories)
3. Missing relationships (characters that should interact but don't)
4. Unresolved plot threads
5. Tag inconsistencies

OUTPUT FORMAT (JSON):
{
  "timeline_gaps": [...],
  "character_gaps": [...],
  "relationship_gaps": [...],
  "thread_gaps": [...],
  "tag_issues": [...]
}
```

**Thread Suggestion Prompt**:
```
You are a creative LARP story writer for "{LARP_TITLE}".

CHARACTERS:
{SELECTED_CHARACTERS}

EXISTING THREADS:
{EXISTING_THREADS}

TAGS: {SELECTED_TAGS}

TASK: Suggest 3 new story threads that:
1. Involve the selected characters
2. Match the selected tags
3. Don't duplicate existing threads
4. Create interesting conflicts/connections

OUTPUT FORMAT (JSON):
{
  "suggestions": [
    {
      "title": "...",
      "summary": "...",
      "acts": ["...", "..."],
      "characters": [id1, id2],
      "tags": ["tag1", "tag2"],
      "rationale": "Why this thread fits..."
    }
  ]
}
```

## Security and Privacy Considerations

### Data Privacy

1. **User Consent**:
   - Add "AI Features" consent in user settings
   - Explain data sent to third-party APIs
   - Allow per-LARP AI opt-out

2. **Data Minimization**:
   - Only send necessary story object data
   - Exclude personal user information
   - Sanitize HTML before sending to AI

3. **Audit Trail**:
   - Log all AI API requests
   - Track costs per LARP
   - Store generation history

### API Key Security

1. **Environment Variables**:
   - Store API keys in `.env.local`
   - Never commit to version control
   - Use Symfony Secrets for production

2. **Key Rotation**:
   - Support multiple API keys
   - Rotate keys periodically
   - Monitor usage limits

### Rate Limiting

1. **Per-User Limits**:
   - Max 10 AI requests per hour per user
   - Max 100 requests per day per LARP
   - Admin override capability

2. **Graceful Degradation**:
   - Queue requests if rate limited
   - Show helpful error messages
   - Fallback to cached suggestions

## Testing Strategy

### Unit Tests

Located in: `tests/Domain/StoryAI/Service/`

Test coverage:
- AIProvider implementations (mock API responses)
- ContextBuilder (vector search logic)
- PromptBuilder (template rendering)
- Each suggestion service

### Integration Tests

Located in: `tests/Domain/StoryAI/Integration/`

Test coverage:
- End-to-end generation flow
- Vector DB operations
- Real AI provider calls (dev environment only)

### Manual Testing Checklist

- [ ] Create LARP with sample data
- [ ] Trigger context build
- [ ] Verify embeddings in vector DB
- [ ] Run gap analysis
- [ ] Generate thread suggestions
- [ ] Accept and create thread from suggestion
- [ ] Verify suggestion history tracking
- [ ] Test with different AI providers
- [ ] Test rate limiting
- [ ] Test error handling (API down, invalid response)

## Monitoring and Observability

### Metrics to Track

1. **Usage Metrics**:
   - AI requests per day/week/month
   - Requests by feature type
   - Requests by LARP

2. **Performance Metrics**:
   - Average response time
   - P95/P99 response times
   - Vector search time

3. **Cost Metrics**:
   - API costs per LARP
   - Token usage per request
   - Cost per feature type

4. **Quality Metrics**:
   - User ratings of suggestions
   - Acceptance rate of suggestions
   - Error rate by provider

### Logging

Log entries should include:
- Request ID
- User ID
- LARP ID
- Feature type
- Provider used
- Token count
- Response time
- Cost
- Success/error status

## Cost Estimation

### OpenAI (GPT-4 Turbo)

**Assumptions**:
- Average context: 5K tokens
- Average completion: 1K tokens
- Input cost: $0.01/1K tokens
- Output cost: $0.03/1K tokens

**Cost per request**: $0.01 × 5 + $0.03 × 1 = **$0.08**

**Monthly cost** (100 requests/month): **$8.00**

### Claude 3 Sonnet

**Assumptions**:
- Average context: 5K tokens
- Average completion: 1K tokens
- Input cost: $0.003/1K tokens
- Output cost: $0.015/1K tokens

**Cost per request**: $0.003 × 5 + $0.015 × 1 = **$0.03**

**Monthly cost** (100 requests/month): **$3.00**

### Ollama (Local)

**Cost**: $0 (hardware/electricity costs only)

## Migration and Deployment

### Database Migrations

1. Create AIGenerationRequest table
2. Create AIGenerationResult table
3. Create LarpContextSnapshot table
4. Create larp_embeddings table (if using pgvector)
5. Add AI-related settings to User entity

### Configuration Steps

1. Choose AI provider(s)
2. Add environment variables
3. Set up vector DB (if not using pgvector)
4. Update Docker Compose (if using ChromaDB)
5. Run migrations
6. Build initial embeddings for existing LARPs

### Rollout Strategy

1. **Alpha** (internal testing):
   - Enable for admin users only
   - Test with 1-2 sample LARPs
   - Collect feedback

2. **Beta** (limited release):
   - Enable for opt-in users
   - Monitor costs and performance
   - Refine prompts based on feedback

3. **General Availability**:
   - Enable for all users
   - Add to onboarding flow
   - Promote in feature announcements

## Future Enhancements

### Potential Features

1. **Character Generation**:
   - Generate complete character profiles
   - Suggest character arcs
   - Auto-generate NPC details

2. **World Building**:
   - Generate location descriptions
   - Suggest faction dynamics
   - Create historical timelines

3. **Player Matching**:
   - Suggest character assignments based on player preferences
   - Analyze player writing samples
   - Recommend character combinations

4. **Real-Time Assistance**:
   - During-event story suggestions
   - Plot twist generator
   - Improvisation prompts for GMs

5. **Multilingual Support**:
   - Generate content in multiple languages
   - Translate existing content
   - Cultural adaptation suggestions

## References

- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Anthropic Claude API](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [Ollama Documentation](https://ollama.com/docs)
- [ChromaDB Documentation](https://docs.trychroma.com/)
- [pgvector GitHub](https://github.com/pgvector/pgvector)
- [HuggingFace Inference API](https://huggingface.co/docs/api-inference)

## Appendix: Example Workflows

### Workflow 1: First-Time Setup

1. Admin navigates to "Story AI Settings"
2. Selects AI provider (OpenAI/Claude/Ollama)
3. Enters API key (if needed)
4. Clicks "Build Context" for LARP
5. System generates embeddings (progress bar)
6. Context ready, AI features unlocked

### Workflow 2: Gap Analysis

1. User opens LARP backoffice
2. Clicks "AI Tools" → "Analyze Story Gaps"
3. System retrieves context from vector DB
4. Sends to AI provider
5. Displays results:
   - Timeline gaps with specific dates
   - Character gaps with missing backstory elements
   - Missing relationships
6. User clicks "Create Thread" on a gap
7. Pre-fills thread form with AI suggestion

### Workflow 3: Thread Suggestion

1. User clicks "AI Tools" → "Suggest Threads"
2. Selects characters (e.g., "John Smith", "Jane Doe")
3. Selects tags (e.g., "mystery", "romance")
4. Clicks "Generate Suggestions"
5. System queries vector DB for relevant context
6. AI generates 3 thread suggestions
7. User reviews suggestions, clicks "Accept" on one
8. System creates Thread entity with AI-generated content
9. User edits and publishes thread

### Workflow 4: Incremental Context Update

1. User creates new character "Lord Blackwood"
2. System triggers `character.created` event
3. Event listener calls ContextBuilder
4. ContextBuilder generates embedding for new character
5. Embedding stored in vector DB
6. LarpContextSnapshot.updated_at timestamp updated
7. Next AI request includes new character in context

---

**Document Version**: 1.0
**Last Updated**: 2025-10-28
**Author**: AI System Documentation
**Status**: Draft - Ready for Implementation
