# Domain Architecture

## Overview

LARPilot is transitioning from a Symfony monolith to a **domain-driven modular architecture**. This document outlines the domain structure, boundaries, and organization principles.

## Architecture Approach

We use **Option A: Single Repository with Domain Namespaces**:
- Monorepo structure with clear domain boundaries
- Domain-based folder organization under `src/Domain/`
- Each domain is self-contained with its entities, services, controllers, forms, and templates
- Shared utilities live in the **Infrastructure** domain (Shared Kernel)
- PHPStan rules enforce domain boundaries

## Domain Structure

Each domain follows this structure:

```
src/Domain/{DomainName}/
    ├── Entity/              # Domain entities
    ├── Repository/          # Data access layer
    ├── Service/             # Domain business logic
    ├── DTO/                 # Data Transfer Objects
    ├── UseCase/             # Application use cases (Commands/Queries/Handlers)
    ├── Event/               # Domain events
    ├── Exception/           # Domain-specific exceptions
    ├── ValueObject/         # Value objects
    ├── Enum/                # Domain-specific enums
    ├── Controller/          # HTTP layer (Backoffice or Public)
    ├── Form/                # Symfony forms
    │   ├── Type/           # Form types
    │   ├── Filter/         # Filter forms
    │   └── DataTransformer/ # Form data transformers
    └── Validator/           # Domain-specific validators

templates/domain/{domain_name}/
    ├── entity_name/         # Entity-specific templates
    │   ├── list.html.twig
    │   ├── modify.html.twig
    │   ├── detail.html.twig
    │   └── _partials/      # Template partials
    └── _components/         # Domain-specific components
```

## Domain List

### 1. Infrastructure (Shared Kernel)
**Scope**: Cross-cutting concerns and shared utilities

**Key Components**:
- Interfaces: `TargetableInterface`, `LarpAwareInterface`, `CreatorAwareInterface`
- Traits: `UuidTraitEntity`, `TimestampableEntity`, `CreatorAwareTrait`
- Services: `TargetResolver`, `DTOPaginationAdapter`
- Base classes: `BaseRepository`, `BaseController`
- Form extensions: `FindOrCreateEntityExtension`
- Shared validators

**No Templates**: Global templates live in `/templates` root

---

### 2. Account
**Scope**: User authentication and account management

**Entities**: `User`, `UserSocialAccount`

**Key Features**:
- OAuth authentication (Google, Facebook, Discord)
- User registration and profile management
- Social account linking

**Use Cases**:
- `RegisterUser`
- `AddSocialAccountToUser`

**Templates**: `templates/domain/account/`

---

### 3. Public
**Scope**: Public-facing pages (LARP discovery, player portal)

**Key Features**:
- Public LARP list and detail pages
- Character sheet viewer
- Landing page

**Controllers**:
- `LarpController`: Public LARP browsing
- `CharacterSheetController`: Public character viewing
- `HomeController`: Landing page

**Templates**: `templates/domain/public/`

---

### 4. Larp (Core Aggregate)
**Scope**: LARP event lifecycle and management

**Entities**: `Larp`, `Skill`, `LarpInvitation`, `Location`

**Key Features**:
- LARP creation, editing, and lifecycle management
- Workflow-based status transitions (DRAFT → WIP → PUBLISHED → CONFIRMED)
- Dashboard and suggestions
- Location management

**Enums**: `LarpStageStatus`, `LarpType`, `LarpSetting`, `LarpCharacterSystem`, `LocationType`

**Services**:
- `LarpManager`: Core LARP operations
- `LarpDashboardService`: Dashboard data aggregation
- `LarpWorkflowService`: Workflow state management
- `SuggestionService`: Intelligent suggestions for LARP setup

**Templates**: `templates/domain/larp/`

---

### 5. StoryObject (Core)
**Scope**: Story elements (characters, threads, quests, etc.) and their relationships

**Entities**: `StoryObject` (abstract base), `Character`, `Thread`, `Quest`, `Event`, `Faction`, `Item`, `Place`, `Relation`, `Tag`, `StoryObjectLogEntry`

**Key Features**:
- Single Table Inheritance (JOINED strategy) for all story types
- Relationship graph (implicit and explicit relations)
- Version tracking via Gedmo Loggable
- Decision tree editor for Quests and Threads
- Story graph visualization (Cytoscape)

**Services**:
- `GraphNodeBuilder`, `GraphEdgeBuilder`: Graph visualization
- `ImplicitRelationBuilder`: Auto-detect relationships
- `StoryObjectVersionService`: Version history
- `StoryObjectTextLinker`: Link story objects in text
- `StoryObjectRouter`: Dynamic routing for polymorphic entities

**Frontend**:
- `story_graph_controller.js`: Cytoscape graph visualization
- `decision_tree_controller.js`: Interactive decision tree editor

**Templates**: `templates/domain/story_object/`

---

### 6. Application
**Scope**: Player applications and character selection (matching system)

**Entities**: `LarpApplication`, `LarpApplicationChoice`, `LarpApplicationVote`

**Key Features**:
- Player application submission
- Character choice ranking
- Voting system for organizers
- Matching algorithm to assign characters

**Services**:
- `ApplicationMatchService`: Matching algorithm
- `SubmissionStatsService`: Application statistics
- `LarpApplicationDashboardService`: Dashboard for applications

**DTOs**: `ApplicationChoiceDTO`, `CharacterMatchDTO`, `UserVoteDTO`, `VoteStatsDTO`

**Templates**: `templates/domain/application/`

**Dependencies**: StoryObject (Character selection), Larp, Participant

---

### 7. Participant
**Scope**: LARP participant management (players, GMs, staff)

**Entities**: `LarpParticipant`

**Key Features**:
- Participant roster management
- Invitation generation with unique codes
- Role assignment (player, GM, staff)

**Services**:
- `ParticipantCodeGenerator`: Generate unique invitation codes
- `ParticipantCodeValidator`: Validate codes

**Use Cases**: `GenerateInvitation`

**Templates**: `templates/domain/participant/`

**Dependencies**: Account (User linkage), Larp

---

### 8. StoryMarketplace
**Scope**: Recruitment system for quests/threads

**Entities**: `StoryRecruitment`, `RecruitmentProposal`

**Key Features**:
- Browse available story opportunities
- Character recruitment proposals
- Quest/thread recruitment management

**Services**: `MarketplaceService`

**Templates**: `templates/domain/story_marketplace/`

**Dependencies**: StoryObject (Quest, Thread, Character)

---

### 9. Kanban
**Scope**: Task management for LARP organization

**Entities**: `KanbanTask`

**Key Features**:
- Kanban board with drag-and-drop (SortableJS)
- Task status tracking
- LARP-specific task organization

**Frontend**: `kanban_controller.js`

**Templates**: `templates/domain/kanban/`

**Dependencies**: Larp

---

### 10. Incident
**Scope**: Incident reporting and tracking during LARPs

**Entities**: `LarpIncident`

**Key Features**:
- Incident report submission
- Tracking and resolution
- Safety incident management

**Components**: `IncidentFormComponent`

**Templates**: `templates/domain/incident/`

**Dependencies**: Larp

---

### 11. Map
**Scope**: Geographic mapping and location management

**Entities**: `GameMap`, `MapLocation`

**Key Features**:
- Interactive game maps
- Location pin placement
- Map visualization

**Templates**: `templates/domain/map/`

**Dependencies**: Larp (shared `Location` entity)

---

### 12. EventPlanning
**Scope**: Event scheduling, resource management, conflict detection

**Entities**: `ScheduledEvent`, `PlanningResource`, `ResourceBooking`, `ScheduledEventConflict`

**Key Features**:
- Event scheduling with FullCalendar
- Resource booking (NPCs, staff, props, equipment)
- Conflict detection (double-booking)
- Setup/cleanup time buffers

**Enums**: `EventStatus`, `BookingStatus`, `PlanningResourceType`, `ConflictType`, `ConflictSeverity`

**Services**: `ConflictDetectionService`

**Frontend**: `event_calendar_controller.js`

**Templates**: `templates/domain/event_planning/`

**Dependencies**: StoryObject (Quest/Thread references), Map (MapLocation), Participant (staff/NPC assignment)

---

### 13. Integration
**Scope**: External service integrations (Google Sheets, Docs, Calendar)

**Entities**: `LarpIntegration`, `ExternalReference`, `ObjectFieldMapping`, `SharedFile`

**Key Features**:
- Google Sheets character import
- Google Docs character sheet mapping
- Google Calendar event sync
- OAuth token management
- File sharing permissions

**Services**:
- `IntegrationManager`: Central integration orchestrator
- `GoogleIntegrationService`: Google API wrapper
- `GoogleSpreadsheetIntegrationHelper`: Spreadsheet operations
- `GoogleDriveSharingService`: File sharing

**Use Cases**: `SaveFileMapping`, `ApplyFilesPermission`, `ImportCharacters`

**Templates**: `templates/domain/integration/`

**Dependencies**: StoryObject (Character import)

---

## Domain Dependency Map

```
Infrastructure (Shared Kernel)
    ↑
    ├── Account
    ├── Public
    ├── Larp ← Participant ← Application
    │   ↑          ↑
    │   │          └─── Integration
    │   ├── StoryObject
    │   │   ↑
    │   │   ├── StoryMarketplace
    │   │   ├── EventPlanning → Map
    │   │   └── Integration
    │   ├── Kanban
    │   ├── Incident
    │   └── Map
```

**Key Dependency Rules**:
- **Application** depends on **StoryObject** (Character selection), **Larp**, **Participant**
- **StoryMarketplace** depends on **StoryObject** (Quest/Thread/Character)
- **EventPlanning** depends on **StoryObject** (story references), **Map** (locations), **Participant** (staff)
- **Integration** depends on **StoryObject** (character import)
- **Participant** depends on **Account** (User linkage), **Larp**
- All domains depend on **Infrastructure** (shared utilities)
- Most domains depend on **Larp** (bounded context)

---

## Cross-Domain Communication

### Synchronous Communication
Use **Domain Services** for cross-domain logic:
- Pass IDs or DTOs between domains (avoid direct entity references)
- Example: `ApplicationMatchService` receives Character IDs from StoryObject domain

### Asynchronous Communication
Use **Domain Events** for decoupled communication:
- Example: `CharacterCreatedEvent` → triggers notification in Application domain
- Symfony Messenger for event bus (future implementation)

### Shared Entities
Some entities are shared across domains:
- `Location`: Used by Larp and Map domains → Keep in **Larp** domain
- `User`: Keep in **Account** domain, reference via ID elsewhere
- `Larp`: Central aggregate, referenced by all domains

---

## Template Organization

### Global Templates (`/templates`)
- `base.html.twig`: Layout base
- `includes/`: Global includes (filter_form, pagination, sort_th, delete_modal)
- `macros/`: Global macros
- `components/`: Global Twig Live Components

### Domain Templates (`/templates/domain/{domain_name}/`)
- Entity-specific templates organized by entity name
- Partials in `_partials/` subdirectories
- Domain-specific components in `_components/`

**Example**:
```
templates/domain/story_object/
    ├── character/
    │   ├── list.html.twig
    │   ├── modify.html.twig
    │   ├── detail.html.twig
    │   └── _partials/
    │       └── character_card.html.twig
    └── thread/
        ├── list.html.twig
        └── tree.html.twig
```

---

## Frontend Assets

### Controllers
- **Domain-specific**: Organized by feature (e.g., `kanban_controller.js` for Kanban domain)
- **Shared**: Infrastructure controllers (e.g., `custom-autocomplete_controller.js`, `wysiwyg_controller.js`)

### Styles
- Global styles in `assets/styles/app.scss`
- Domain-specific styles in `assets/styles/domain/` (optional)

---

## Routing Organization

Routes are organized by domain using Symfony's attribute routing:

```yaml
# config/routes.yaml
domain_public:
    resource: '../src/Domain/Public/Controller/'
    type: attribute
    prefix: /

domain_larp:
    resource: '../src/Domain/Core/Controller/'
    type: attribute
    prefix: /backoffice/larp

domain_story_object:
    resource: '../src/Domain/StoryObject/Controller/'
    type: attribute
    prefix: /backoffice/larp/{larp}/story
```

---

## Service Configuration

Domain services are auto-configured via namespace:

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # All domain services
    App\Domain\:
        resource: '../src/Domain/*'
        exclude:
            - '../src/Domain/*/Entity/'
            - '../src/Domain/*/DTO/'
            - '../src/Domain/*/ValueObject/'

    # Domain-specific tags (optional)
    App\Domain\StoryObject\Service\:
        resource: '../src/Domain/StoryObject/Service/'
        tags: ['domain.story_object.service']
```

---

## Domain Boundary Rules (PHPStan)

PHPStan enforces domain boundaries to prevent violations:

1. **Infrastructure** can be imported by all domains
2. **Larp** can be imported by most domains (central aggregate)
3. **StoryObject** can be imported by Application, StoryMarketplace, EventPlanning, Integration
4. **Account** can be imported by Participant, Public
5. **No circular dependencies** between domains

See `phpstan.neon` for detailed rules.

---

## Migration Strategy

### Phase 1: Foundation (Weeks 1-2)
1. Create domain folder structure
2. Move **Infrastructure** shared utilities
3. Move **Account** domain
4. Configure service autowiring and routing

### Phase 2: Standalone Modules (Weeks 3-4)
5. Move **Kanban** domain (simplest, proof of concept)
6. Move **Incident** domain
7. Move **Map** domain
8. Move **Public** domain

### Phase 3: Integration & Planning (Weeks 5-6)
9. Move **Integration** domain
10. Move **EventPlanning** domain

### Phase 4: Core Domains (Weeks 7-9)
11. Move **Larp** domain (core aggregate)
12. Move **Participant** domain
13. Move **StoryObject** domain (most complex)

### Phase 5: Story-Related (Weeks 10-11)
14. Move **StoryMarketplace** domain
15. Move **Application** domain

### Phase 6: Testing & Refinement (Week 12)
16. Add comprehensive PHPStan boundary rules
17. Update all tests
18. Final documentation updates

---

## Benefits

1. **Clear Boundaries**: Each domain has well-defined responsibilities
2. **Testability**: Domains can be tested independently
3. **Maintainability**: Changes isolated to specific domains
4. **Team Scalability**: Different teams can own different domains
5. **Reusability**: Some domains (Kanban, Incident) could be extracted to separate bundles
6. **Flexibility**: Can evolve to Symfony bundles or microservices later
7. **Discoverability**: Easy to find code related to a feature
8. **Parallel Development**: Reduced merge conflicts, teams work independently

---

## Best Practices

### Entity References
- **Within Domain**: Direct entity references OK
- **Cross-Domain**: Use IDs or DTOs
- **Shared Entities**: Reference via interface when possible

### Service Naming
- Use descriptive names: `ApplicationMatchService` not `MatchService`
- Domain name prefix when ambiguous: `LarpDashboardService`

### Template Naming
- Follow entity name structure: `{entity}/list.html.twig`, `{entity}/modify.html.twig`
- Partials prefix with underscore: `_partials/card.html.twig`

### Form Naming
- Entity forms: `{Entity}Type` (e.g., `CharacterType`)
- Filter forms: `{Entity}FilterType` (e.g., `CharacterFilterType`)

### Controller Actions
- Standard CRUD: `list()`, `modify()`, `delete()`, `detail()`
- Custom actions: descriptive names (`match()`, `vote()`, `graph()`)

---

## Future Considerations

### Symfony Bundles
If domains become highly reusable, consider extracting to bundles:
- `LARPilot/KanbanBundle`
- `LARPilot/IncidentBundle`

### Microservices
Only consider if facing scaling issues:
- Authentication service (Account domain)
- Story service (StoryObject domain)
- Integration service (Integration domain)

### Domain Events
Implement Symfony Messenger for async domain events:
- Decouple domain communication
- Enable eventual consistency
- Improve performance

---

## Resources

- [Domain-Driven Design by Eric Evans](https://www.domainlanguage.com/ddd/)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)

---

**Last Updated**: 2025-10-12
**Status**: Planning / Early Implementation
