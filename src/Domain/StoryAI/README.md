# StoryAI Domain

AI-powered story generation and analysis for LARP content.

## Overview

This domain provides intelligent story assistance features including:
- **Thread Suggestions**: AI-generated story threads based on characters and tags
- **Gap Analysis**: Detect gaps in timelines, character backstories, and relationships
- **Quest/Event Suggestions**: Generate quest and event ideas
- **Semantic Search**: Find relevant story content using vector embeddings

## Status

**Current Status**: 🏗️ Foundation complete, ready for Phase 1 implementation

See `docs/STORY_AI_IMPLEMENTATION_ROADMAP.md` for detailed implementation plan.

## Architecture

### Domain Structure

```
src/Domain/StoryAI/
├── Entity/                          # Domain entities
│   ├── AIGenerationRequest.php     # Tracks AI requests
│   └── AIGenerationResult.php      # Stores AI responses
│
├── Repository/                      # Data access
│   ├── AIGenerationRequestRepository.php
│   └── AIGenerationResultRepository.php
│
├── Service/                         # Business logic
│   ├── AIProviderInterface.php     # ✅ Provider abstraction
│   └── ThreadSuggestionService.php # ✅ Thread generation (needs implementation)
│
├── DTO/                            # Data Transfer Objects
│   ├── AIRequestDTO.php            # ✅ Request structure
│   ├── AIResponseDTO.php           # ✅ Response structure
│   └── ThreadSuggestionDTO.php     # ✅ Thread suggestion data
│
├── Controller/
│   ├── Backoffice/
│   │   └── StoryAIController.php   # ✅ Main UI (needs implementation)
│   └── API/
│       └── StoryAIApiController.php # TODO: AJAX endpoints
│
└── Form/
    └── Type/
        └── (to be created)          # TODO: Filter forms
```

### Key Interfaces

#### AIProviderInterface

Common abstraction for all AI providers (OpenAI, Claude, Ollama).

```php
interface AIProviderInterface
{
    public function generate(AIRequestDTO $request): AIResponseDTO;
    public function getName(): string;
    public function isAvailable(): bool;
    public function estimateCost(AIRequestDTO $request): int;
}
```

**Implementations needed**:
- [ ] `OpenAIProvider.php` (Phase 1)
- [ ] `OllamaProvider.php` (Phase 1)
- [ ] `ClaudeProvider.php` (Phase 5)

#### VectorStoreInterface (Phase 2)

Abstraction for vector database operations.

```php
interface VectorStoreInterface
{
    public function insert(string $id, array $embedding, array $metadata): void;
    public function search(array $queryEmbedding, int $limit): array;
    public function delete(string $id): void;
}
```

**Implementations planned**:
- [ ] `PgVectorStore.php` (Phase 2)
- [ ] `ChromaDBStore.php` (Phase 2, optional)

## Features

### ✅ Implemented
- Entity structure for request/result tracking
- DTO definitions
- Service interfaces
- Controller structure
- Basic UI templates

### 🔨 In Progress (Phase 1)
- AI provider implementations (OpenAI, Ollama)
- Thread suggestion service
- Basic context building
- Prompt templates
- Form and UI completion

### 📋 Planned

**Phase 2: Vector Database**
- Embedding generation
- Vector storage and search
- Incremental context updates
- Semantic search

**Phase 3: Gap Analysis**
- Timeline gap detection
- Character history gaps
- Missing relationships
- Unresolved threads

**Phase 4: Content Suggestions**
- Quest suggestions
- Event suggestions
- "Accept suggestion" workflow

**Phase 5: Advanced Features**
- Multi-provider support
- Cost tracking
- User feedback system
- Custom prompt templates
- Batch generation

## Configuration

Required environment variables:

```env
# AI Provider
AI_PROVIDER=ollama              # ollama|openai|claude

# OpenAI (if AI_PROVIDER=openai)
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo-preview

# Claude (if AI_PROVIDER=claude)
CLAUDE_API_KEY=sk-ant-...
CLAUDE_MODEL=claude-3-sonnet-20240229

# Ollama (if AI_PROVIDER=ollama)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=mixtral:8x7b

# Embeddings (Phase 2)
EMBEDDING_PROVIDER=openai
```

See `docs/STORY_AI_SETUP.md` for complete setup instructions.

## Usage

### Thread Suggestions (Phase 1)

```php
use App\Domain\StoryAI\Service\ThreadSuggestionService;

// In controller
$suggestions = $threadSuggestionService->generateSuggestions(
    larp: $larp,
    user: $this->getUser(),
    characterIds: [1, 2, 3],
    tags: ['mystery', 'magic']
);
```

### Context Building (Phase 2)

```bash
# Build vector DB context for a LARP
php bin/console app:story-ai:build-context 1
```

### Gap Analysis (Phase 3)

```php
use App\Domain\StoryAI\Service\StoryGapAnalyzer;

$gaps = $gapAnalyzer->analyze($larp);
// Returns: timeline gaps, character gaps, relationship gaps
```

## Testing

```bash
# Run all StoryAI tests
vendor/bin/phpunit tests/Domain/StoryAI/

# Test specific service
vendor/bin/phpunit tests/Domain/StoryAI/Service/ThreadSuggestionServiceTest.php
```

## Implementation Guide

See detailed guides in `docs/`:

1. **Overview**: `docs/technical/STORY_AI_SYSTEM.md`
   - Architecture decisions
   - AI provider comparison
   - Cost estimates
   - Security considerations

2. **Vector DB**: `docs/technical/VECTOR_DB_IMPLEMENTATION.md`
   - Step-by-step pgvector setup
   - Embedding generation
   - Semantic search implementation
   - Performance optimization

3. **Setup**: `docs/STORY_AI_SETUP.md`
   - Environment configuration
   - Provider setup (OpenAI/Ollama/Claude)
   - Troubleshooting

4. **Roadmap**: `docs/STORY_AI_IMPLEMENTATION_ROADMAP.md`
   - Phase-by-phase tasks
   - Time estimates
   - Success metrics
   - How to resume work

## Development Workflow

1. **Pick a task** from roadmap (start with Phase 1)
2. **Review existing code** and TODOs
3. **Implement feature** following SOLID/KISS/YAGNI principles
4. **Write tests** for new functionality
5. **Update documentation** if needed
6. **Manual testing** before moving to next task

## Key Principles

### SOLID
- **Single Responsibility**: Each service has one clear purpose
- **Open/Closed**: Use interfaces for extensibility (AIProviderInterface)
- **Liskov Substitution**: All providers work interchangeably
- **Interface Segregation**: Small, focused interfaces
- **Dependency Inversion**: Depend on abstractions, not concrete classes

### KISS (Keep It Simple, Stupid)
- Start with simplest solution (pgvector before ChromaDB)
- Don't over-engineer (Phase 1 doesn't need async)
- Clear, readable code over clever tricks

### YAGNI (You Aren't Gonna Need It)
- Implement features when needed, not "just in case"
- Phase 1 features first, advanced features later
- Don't add configuration for things not yet implemented

## Dependencies

### Current
- Doctrine ORM (entity management)
- Symfony HTTP Client (API calls)
- Symfony Console (CLI commands)

### Planned
- pgvector (Phase 2, vector database)
- Symfony Messenger (Phase 5, async processing)

## Contributing

When adding new features:

1. Follow existing domain structure
2. Add entities to `Entity/`
3. Add repositories to `Repository/`
4. Add business logic to `Service/`
5. Add DTOs to `DTO/`
6. Add controllers to `Controller/Backoffice/` or `Controller/API/`
7. Add forms to `Form/Type/`
8. Update this README if structure changes
9. Add TODOs for incomplete work
10. Write tests!

## Support

For questions or issues:

1. Check `docs/STORY_AI_SYSTEM.md` for architecture details
2. Check `docs/STORY_AI_SETUP.md` for setup help
3. Check `docs/STORY_AI_IMPLEMENTATION_ROADMAP.md` for task details
4. Create GitHub issue with specific question

## License

Part of LARPilot project. See main LICENSE file.

---

**Last Updated**: 2025-10-28
**Status**: Foundation Complete, Phase 1 Ready
**Maintainer**: Development Team
