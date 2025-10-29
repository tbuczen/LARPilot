# Story AI Implementation Roadmap

This document provides a step-by-step roadmap for implementing the AI-powered story generation features.

## Overview

The implementation is divided into 5 phases, each building on the previous one. You can pick up work at any phase by reviewing the relevant TODOs in the codebase.

**Current Status**: ✅ Foundation complete, ready for Phase 1 implementation

## Phase 1: Basic AI Integration (MVP) - 2-3 days

**Goal**: Get a working AI provider integration with simple thread suggestions.

**Status**: 🔨 In Progress - Foundation laid, implementation needed

### Tasks

1. **Create AI Provider Implementations** (4-6 hours)
   - [ ] Implement `OpenAIProvider.php` (implements `AIProviderInterface`)
   - [ ] Implement `OllamaProvider.php` (implements `AIProviderInterface`)
   - [ ] Add basic error handling and retry logic
   - [ ] Add unit tests with mocked API responses
   - **Files to create**:
     - `src/Domain/StoryAI/Service/OpenAIProvider.php`
     - `src/Domain/StoryAI/Service/OllamaProvider.php`
     - `tests/Domain/StoryAI/Service/OpenAIProviderTest.php`

2. **Implement Basic Context Building** (2-3 hours)
   - [ ] Update `ThreadSuggestionService::buildContext()` to query character/thread data
   - [ ] Create simple text formatting for LARP context
   - [ ] Add character filtering logic
   - [ ] Add tag filtering logic
   - **Files to modify**:
     - `src/Domain/StoryAI/Service/ThreadSuggestionService.php:buildContext()`

3. **Implement Prompt Templates** (2-3 hours)
   - [ ] Create `PromptTemplateService.php` for managing prompt templates
   - [ ] Create template for thread suggestions
   - [ ] Add variable substitution (LARP title, characters, tags)
   - [ ] Test prompt generation with sample data
   - **Files to create**:
     - `src/Domain/StoryAI/Service/PromptTemplateService.php`
     - `templates/prompts/thread_suggestion.txt.twig`

4. **Create Basic UI** (3-4 hours)
   - [ ] Implement thread suggestion form in `StoryAIController::threadSuggestions()`
   - [ ] Create `ThreadSuggestionFilterType` form with character/tag filters
   - [ ] Create template `templates/backoffice/story_ai/suggestions/threads.html.twig`
   - [ ] Display results with cards/table
   - [ ] Add loading spinner during generation
   - **Files to modify**:
     - `src/Domain/StoryAI/Controller/Backoffice/StoryAIController.php`
   - **Files to create**:
     - `src/Domain/StoryAI/Form/Type/ThreadSuggestionFilterType.php`
     - `templates/backoffice/story_ai/suggestions/threads.html.twig`

5. **Add Service Configuration** (1 hour)
   - [ ] Configure service wiring in `config/services.yaml`
   - [ ] Add provider selection logic (based on `AI_PROVIDER` env var)
   - [ ] Test with both OpenAI and Ollama
   - **Files to modify**:
     - `config/services.yaml`

6. **Manual Testing** (2 hours)
   - [ ] Set up OpenAI API key or Ollama
   - [ ] Create test LARP with 5+ characters
   - [ ] Generate thread suggestions with different filters
   - [ ] Verify results are relevant and well-formatted
   - [ ] Test error handling (invalid API key, network error)

**Phase 1 Completion Criteria**:
- ✅ Thread suggestions working with at least one AI provider
- ✅ Basic UI for generating and viewing suggestions
- ✅ Request/response tracking in database
- ✅ Error handling for API failures

**Estimated Time**: 14-19 hours (2-3 days)

---

## Phase 2: Vector Database Integration - 2-3 days

**Goal**: Add semantic search and efficient context retrieval using embeddings.

**Prerequisites**: Phase 1 complete

### Tasks

1. **Set Up pgvector** (2-3 hours)
   - [ ] Update docker-compose.yml to use `pgvector/pgvector:pg16` image
   - [ ] Create migration for `larp_embedding` table (see VECTOR_DB_IMPLEMENTATION.md)
   - [ ] Run migration and verify table creation
   - [ ] Create `LarpEmbedding` entity
   - [ ] Create `LarpEmbeddingRepository` with vector search methods
   - **Files to create**:
     - `migrations/VersionXXXXXXXXXXXXXX.php` (generated)
     - `src/Domain/StoryAI/Entity/LarpEmbedding.php`
     - `src/Domain/StoryAI/Repository/LarpEmbeddingRepository.php`

2. **Implement Embedding Service** (3-4 hours)
   - [ ] Create `EmbeddingService.php` (see VECTOR_DB_IMPLEMENTATION.md)
   - [ ] Implement OpenAI embedding generation
   - [ ] Add batch embedding support
   - [ ] Add error handling and rate limiting
   - [ ] Test with sample text
   - **Files to create**:
     - `src/Domain/StoryAI/Service/EmbeddingService.php`
     - `tests/Domain/StoryAI/Service/EmbeddingServiceTest.php`

3. **Implement Context Builder** (4-5 hours)
   - [ ] Create `ContextBuilderService.php` (see VECTOR_DB_IMPLEMENTATION.md)
   - [ ] Implement `buildContext()` for full LARP context
   - [ ] Implement `updateEntity()` for incremental updates
   - [ ] Implement `findRelevantContext()` for semantic search
   - [ ] Add content formatting for each entity type
   - **Files to create**:
     - `src/Domain/StoryAI/Service/ContextBuilderService.php`
     - `tests/Domain/StoryAI/Service/ContextBuilderServiceTest.php`

4. **Create Console Command** (1-2 hours)
   - [ ] Create `BuildContextCommand` (see VECTOR_DB_IMPLEMENTATION.md)
   - [ ] Add progress bar for long-running operations
   - [ ] Add option to rebuild specific entity types
   - [ ] Test with sample LARP
   - **Files to create**:
     - `src/Domain/StoryAI/Command/BuildContextCommand.php`

5. **Add Event Listeners** (2-3 hours)
   - [ ] Create `StoryObjectEmbeddingListener` (see VECTOR_DB_IMPLEMENTATION.md)
   - [ ] Listen for Character create/update events
   - [ ] Listen for Thread create/update events
   - [ ] Add for Quest, Event, Faction entities
   - [ ] Test incremental updates
   - **Files to create**:
     - `src/Domain/StoryAI/EventListener/StoryObjectEmbeddingListener.php`

6. **Integrate with Thread Suggestions** (2-3 hours)
   - [ ] Update `ThreadSuggestionService::buildContext()` to use vector search
   - [ ] Replace simple text queries with semantic search
   - [ ] Limit context to top 20 most relevant objects
   - [ ] Test improved relevance
   - **Files to modify**:
     - `src/Domain/StoryAI/Service/ThreadSuggestionService.php:buildContext()`

7. **Testing and Optimization** (2-3 hours)
   - [ ] Test context building for large LARP (100+ characters)
   - [ ] Verify vector search returns relevant results
   - [ ] Benchmark query performance
   - [ ] Add indexes if needed
   - [ ] Test incremental updates

**Phase 2 Completion Criteria**:
- ✅ Vector DB storing embeddings for all LARPs
- ✅ Semantic search returning relevant context
- ✅ Automatic updates when entities change
- ✅ Console command for manual rebuilds
- ✅ Performance acceptable (context build < 5 min for 100 characters)

**Estimated Time**: 16-23 hours (2-3 days)

---

## Phase 3: Story Gap Analysis - 2-3 days

**Goal**: Analyze LARP story for gaps and inconsistencies.

**Prerequisites**: Phase 2 complete

### Tasks

1. **Create Gap Analysis Service** (4-5 hours)
   - [ ] Create `StoryGapAnalyzer.php`
   - [ ] Implement timeline gap detection
   - [ ] Implement character history gap detection
   - [ ] Implement missing relationship detection
   - [ ] Implement unresolved thread detection
   - [ ] Create prompt template for gap analysis
   - **Files to create**:
     - `src/Domain/StoryAI/Service/StoryGapAnalyzer.php`
     - `templates/prompts/gap_analysis.txt.twig`

2. **Create Gap Analysis DTOs** (1-2 hours)
   - [ ] Create `GapAnalysisResultDTO.php`
   - [ ] Create `TimelineGapDTO.php`
   - [ ] Create `CharacterGapDTO.php`
   - [ ] Create `RelationshipGapDTO.php`
   - **Files to create**:
     - `src/Domain/StoryAI/DTO/GapAnalysisResultDTO.php`
     - `src/Domain/StoryAI/DTO/TimelineGapDTO.php`
     - `src/Domain/StoryAI/DTO/CharacterGapDTO.php`
     - `src/Domain/StoryAI/DTO/RelationshipGapDTO.php`

3. **Implement Controller Methods** (2-3 hours)
   - [ ] Implement `StoryAIController::gapAnalysis()`
   - [ ] Handle form submission
   - [ ] Call `StoryGapAnalyzer`
   - [ ] Store results
   - **Files to modify**:
     - `src/Domain/StoryAI/Controller/Backoffice/StoryAIController.php:gapAnalysis()`

4. **Create UI** (3-4 hours)
   - [ ] Create `templates/backoffice/story_ai/gap_analysis/index.html.twig`
   - [ ] Create `templates/backoffice/story_ai/gap_analysis/results.html.twig`
   - [ ] Display gaps by category (timeline, character, relationship)
   - [ ] Add "Create Thread" quick action for each gap
   - [ ] Add export to PDF/CSV functionality
   - **Files to create**:
     - `templates/backoffice/story_ai/gap_analysis/index.html.twig`
     - `templates/backoffice/story_ai/gap_analysis/results.html.twig`

5. **Add Quick Actions** (2-3 hours)
   - [ ] Add "Create Thread from Gap" functionality
   - [ ] Pre-fill thread form with gap details
   - [ ] Add "Mark as Resolved" for gaps
   - [ ] Track which gaps were addressed
   - **Files to modify**:
     - `src/Domain/StoryAI/Controller/Backoffice/StoryAIController.php`

6. **Testing** (2 hours)
   - [ ] Create LARP with intentional gaps
   - [ ] Run gap analysis
   - [ ] Verify all gap types are detected
   - [ ] Test quick actions
   - [ ] Get feedback from beta users

**Phase 3 Completion Criteria**:
- ✅ Gap analysis working for all gap types
- ✅ Clear, actionable results displayed
- ✅ Quick actions to address gaps
- ✅ Results stored for historical tracking

**Estimated Time**: 14-19 hours (2-3 days)

---

## Phase 4: Content Suggestions (Quests & Events) - 2-3 days

**Goal**: Extend suggestion system to quests and events.

**Prerequisites**: Phase 1 complete (Phase 2 helpful but not required)

### Tasks

1. **Create Quest Suggestion Service** (2-3 hours)
   - [ ] Create `QuestSuggestionService.php` (similar to Thread)
   - [ ] Create `QuestSuggestionDTO.php`
   - [ ] Create prompt template for quests
   - [ ] Implement generation logic
   - **Files to create**:
     - `src/Domain/StoryAI/Service/QuestSuggestionService.php`
     - `src/Domain/StoryAI/DTO/QuestSuggestionDTO.php`
     - `templates/prompts/quest_suggestion.txt.twig`

2. **Create Event Suggestion Service** (2-3 hours)
   - [ ] Create `EventSuggestionService.php` (similar to Thread)
   - [ ] Create `EventSuggestionDTO.php`
   - [ ] Create prompt template for events
   - [ ] Implement generation logic
   - **Files to create**:
     - `src/Domain/StoryAI/Service/EventSuggestionService.php`
     - `src/Domain/StoryAI/DTO/EventSuggestionDTO.php`
     - `templates/prompts/event_suggestion.txt.twig`

3. **Add Controller Methods** (2-3 hours)
   - [ ] Add `StoryAIController::questSuggestions()`
   - [ ] Add `StoryAIController::eventSuggestions()`
   - [ ] Create forms for filters
   - **Files to modify**:
     - `src/Domain/StoryAI/Controller/Backoffice/StoryAIController.php`
   - **Files to create**:
     - `src/Domain/StoryAI/Form/Type/QuestSuggestionFilterType.php`
     - `src/Domain/StoryAI/Form/Type/EventSuggestionFilterType.php`

4. **Create UI** (3-4 hours)
   - [ ] Create quest suggestions template
   - [ ] Create event suggestions template
   - [ ] Add to main AI dashboard
   - **Files to create**:
     - `templates/backoffice/story_ai/suggestions/quests.html.twig`
     - `templates/backoffice/story_ai/suggestions/events.html.twig`

5. **Implement "Accept Suggestion"** (3-4 hours)
   - [ ] Create API endpoint for accepting suggestions
   - [ ] Auto-create Thread/Quest/Event from suggestion
   - [ ] Pre-fill all fields
   - [ ] Mark result as accepted in DB
   - [ ] Add JavaScript for smooth UX
   - **Files to create**:
     - `src/Domain/StoryAI/Controller/API/SuggestionAcceptController.php`
     - `assets/controllers/ai_suggestion_controller.js`

6. **Testing** (2 hours)
   - [ ] Test quest suggestions
   - [ ] Test event suggestions
   - [ ] Test accept workflow
   - [ ] Verify created entities are correct

**Phase 4 Completion Criteria**:
- ✅ Quest and event suggestions working
- ✅ Accept suggestion creates real entity
- ✅ UI consistent across all suggestion types
- ✅ Results tracked in database

**Estimated Time**: 14-19 hours (2-3 days)

---

## Phase 5: Advanced Features - 3-5 days

**Goal**: Polish, optimization, and advanced capabilities.

**Prerequisites**: Phases 1-4 complete

### Tasks

1. **Multi-Provider Support** (3-4 hours)
   - [ ] Implement `ClaudeProvider.php`
   - [ ] Add provider selection in UI
   - [ ] Allow per-LARP provider preference
   - [ ] Test provider switching
   - **Files to create**:
     - `src/Domain/StoryAI/Service/ClaudeProvider.php`
     - `src/Domain/StoryAI/Form/Type/AISettingsType.php`

2. **Cost Tracking** (4-5 hours)
   - [ ] Implement `estimateCost()` in all providers
   - [ ] Add cost calculation to request tracking
   - [ ] Create cost dashboard
   - [ ] Add cost alerts (email when over budget)
   - **Files to create**:
     - `src/Domain/StoryAI/Service/CostTrackingService.php`
     - `templates/backoffice/story_ai/costs/dashboard.html.twig`

3. **User Feedback System** (3-4 hours)
   - [ ] Add rating system for suggestions (1-5 stars)
   - [ ] Add "Report Issue" button
   - [ ] Track acceptance rate by feature
   - [ ] Create quality metrics dashboard
   - **Files to modify**:
     - `src/Domain/StoryAI/Entity/AIGenerationResult.php` (add rating field)
   - **Files to create**:
     - `src/Domain/StoryAI/Controller/API/FeedbackController.php`

4. **Custom Prompt Templates** (4-5 hours)
   - [ ] Allow users to edit prompt templates
   - [ ] Store custom templates per LARP
   - [ ] Add template variables documentation
   - [ ] Create template editor UI
   - **Files to create**:
     - `src/Domain/StoryAI/Entity/PromptTemplate.php`
     - `src/Domain/StoryAI/Repository/PromptTemplateRepository.php`
     - `templates/backoffice/story_ai/prompts/editor.html.twig`

5. **Batch Generation** (3-4 hours)
   - [ ] Add "Generate for All Characters" option
   - [ ] Add "Generate for All Threads" option
   - [ ] Use Symfony Messenger for async processing
   - [ ] Add progress tracking
   - **Files to create**:
     - `src/Domain/StoryAI/Message/BatchGenerationMessage.php`
     - `src/Domain/StoryAI/MessageHandler/BatchGenerationHandler.php`

6. **Local Embedding Support** (4-6 hours)
   - [ ] Integrate sentence-transformers Python library
   - [ ] Create Python microservice for embeddings
   - [ ] Add to docker-compose.yml
   - [ ] Update EmbeddingService to support local provider
   - **Files to create**:
     - `docker/embedding-service/Dockerfile`
     - `docker/embedding-service/app.py`
   - **Files to modify**:
     - `docker-compose.yml`
     - `src/Domain/StoryAI/Service/EmbeddingService.php`

7. **Performance Optimization** (3-4 hours)
   - [ ] Add Redis cache for frequent queries
   - [ ] Implement result caching (same prompt = same result)
   - [ ] Optimize vector search with better indexes
   - [ ] Add lazy loading for large result sets

8. **Documentation and Training** (2-3 hours)
   - [ ] Create user guide with screenshots
   - [ ] Record demo video
   - [ ] Add tooltips/help text in UI
   - [ ] Create FAQ

**Phase 5 Completion Criteria**:
- ✅ Multiple AI providers supported
- ✅ Cost tracking and alerts working
- ✅ User feedback system in place
- ✅ Advanced features polished and documented

**Estimated Time**: 26-38 hours (3-5 days)

---

## Quick Reference: File Locations

### Core Services
- `src/Domain/StoryAI/Service/AIProviderInterface.php` - Provider abstraction
- `src/Domain/StoryAI/Service/ThreadSuggestionService.php` - Thread suggestions
- `src/Domain/StoryAI/Service/ContextBuilderService.php` - Vector DB context (Phase 2)
- `src/Domain/StoryAI/Service/EmbeddingService.php` - Generate embeddings (Phase 2)

### Entities & Repositories
- `src/Domain/StoryAI/Entity/AIGenerationRequest.php` - Request tracking
- `src/Domain/StoryAI/Entity/AIGenerationResult.php` - Result storage
- `src/Domain/StoryAI/Entity/LarpEmbedding.php` - Vector storage (Phase 2)
- `src/Domain/StoryAI/Repository/*Repository.php` - Data access

### Controllers
- `src/Domain/StoryAI/Controller/Backoffice/StoryAIController.php` - Main UI
- `src/Domain/StoryAI/Controller/API/StoryAIApiController.php` - AJAX endpoints

### Templates
- `templates/backoffice/story_ai/dashboard.html.twig` - Main dashboard
- `templates/backoffice/story_ai/suggestions/threads.html.twig` - Thread UI
- `templates/prompts/*.txt.twig` - Prompt templates

### Documentation
- `docs/technical/STORY_AI_SYSTEM.md` - Technical overview
- `docs/technical/VECTOR_DB_IMPLEMENTATION.md` - Vector DB guide
- `docs/STORY_AI_SETUP.md` - Setup instructions
- `docs/STORY_AI_IMPLEMENTATION_ROADMAP.md` - This file

## How to Resume Work

1. **Check current phase status**: Review TODOs in relevant files
2. **Run tests**: `make test` to ensure existing functionality works
3. **Review recent commits**: Check git history for context
4. **Pick a task**: Choose from roadmap tasks above
5. **Update TODOs**: Mark completed items, add new ones as needed
6. **Test thoroughly**: Manual testing after each feature
7. **Update documentation**: Keep docs in sync with implementation

## Success Metrics

Track these metrics to measure success:

### Phase 1
- ✅ At least 1 AI provider working
- ✅ Average response time < 10 seconds
- ✅ Error rate < 5%
- ✅ 3+ thread suggestions per request

### Phase 2
- ✅ Context build time < 5 minutes for 100 characters
- ✅ Vector search returns relevant results (manual verification)
- ✅ Incremental updates < 1 second per entity
- ✅ Search recall rate > 80% (relevant items in top 20)

### Phase 3
- ✅ Detects at least 3 types of gaps
- ✅ User rating ≥ 4/5 stars
- ✅ 50%+ of suggestions are actionable
- ✅ Gap analysis time < 30 seconds

### Phase 4
- ✅ Quest/Event suggestions match thread quality
- ✅ "Accept" workflow works smoothly
- ✅ 30%+ acceptance rate for suggestions

### Phase 5
- ✅ Cost tracking accurate within 5%
- ✅ Custom prompts increase acceptance rate by 10%
- ✅ Average user rating ≥ 4.5/5 stars
- ✅ All providers maintain < 5% error rate

## Support

If you get stuck:

1. **Check documentation**: Review STORY_AI_SYSTEM.md and VECTOR_DB_IMPLEMENTATION.md
2. **Review code comments**: Look for `@TODO` and implementation notes
3. **Test individual components**: Unit test each service in isolation
4. **Ask for help**: Create GitHub issue with specific question

---

**Document Version**: 1.0
**Last Updated**: 2025-10-28
**Status**: Ready for Implementation
