# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LARPilot is a Symfony 7.2 application for managing LARP (Live Action Role-Playing) events. The platform integrates with Google services (Sheets, Docs, Calendar) and provides tools for both players and organizers. It uses PostgreSQL as the database and follows a Domain-Driven Design approach with modular architecture.
Local development is performed on Docker, most of the useful commands are available in the Makefile.

## Development Commands

### Setup & Build
```bash
# Install PHP dependencies
make install

# Setup JavaScript environment (run in order)
make assets

# Run database migrations
make migrate
```

### Development Server (Docker)
```bash
make start
```

### Quality Tools
```bash
# Code style check (PSR-12 + import cleanup)
make ecs
# OR: docker compose exec -T php vendor/bin/ecs check

# Fix code style issues
make ecs-fix
# OR: docker compose exec -T php vendor/bin/ecs check --fix

# Static analysis
make stan
# OR: docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 vendor/bin/phpstan analyse -c phpstan.neon"

# Run tests
make test
# OR: docker compose exec -T php bash -lc "APP_ENV=test php vendor/bin/phpunit -c phpunit.xml.dist --colors=always"

# Automated refactoring (PHP 8.2)
make rector-fix
# OR: docker compose exec -T php vendor/bin/rector process
```

**ECS Rules Applied**:
- PSR-12 coding standard
- `NoUnusedImportsFixer`: Removes unused `use` statements
- `FullyQualifiedStrictTypesFixer`: Replaces FQDN class references with imported classes (e.g., `\App\Domain\Account\Entity\User` → `User` with proper import)
- `OrderedImportsFixer`: Sorts imports alphabetically (classes, functions, constants)
- `BlankLineAfterNamespaceFixer`: Ensures blank line after namespace declaration

### Database Operations
All run on docker 
```bash
# Create new migration
php bin/console make:migration

# Apply migrations
php bin/console doctrine:migrations:migrate

# Load development fixtures
php bin/console doctrine:fixtures:load
```

## Architecture

LARPilot follows a **Domain-Driven Design (DDD)** approach with modular architecture. The codebase is organized into domain-based namespaces under `src/Domain/`, with each domain being self-contained. See [`docs/DOMAIN_ARCHITECTURE.md`](docs/DOMAIN_ARCHITECTURE.md) for complete domain organization, boundaries, and migration strategy.

**Key Domains**:
- **Infrastructure** (Shared Kernel): Cross-cutting concerns, shared utilities, base classes
- **Account**: User authentication and profile management
- **Public**: Public-facing LARP discovery and character sheets
- **Larp**: Core LARP event lifecycle (central aggregate)
- **StoryObject**: Story elements (Character, Thread, Quest, Event, Faction, Item, Place, Relation)
- **Application**: Player applications and character matching
- **Participant**: LARP participant management (players, GMs, staff)
- **StoryMarketplace**: Quest/thread recruitment system
- **Kanban**: Task management for LARP organization
- **Incident**: Incident reporting and tracking
- **Map**: Geographic mapping and location management
- **EventPlanning**: Event scheduling, resource management, conflict detection
- **Integration**: External service integrations (Google Sheets, Docs, Calendar)

**Domain Structure**:
```
src/Domain/{DomainName}/
    ├── Entity/              # Domain entities
    ├── Repository/          # Data access layer
    ├── Service/             # Domain business logic
    ├── DTO/                 # Data Transfer Objects
    ├── UseCase/             # Commands/Queries/Handlers
    ├── Controller/          # HTTP layer
    ├── Form/                # Symfony forms (Type, Filter, DataTransformer)
    └── Validator/           # Domain-specific validators

templates/domain/{domain_name}/
    └── entity_name/         # Domain-specific templates
```

**Template Organization**:
- Global templates (base, includes, macros) in `/templates`
- Domain-specific templates in `/templates/domain/{domain_name}/`

### Core Entity Structure

**Larp**: Central entity representing a LARP event. Contains metadata like title, dates, location, status (via Symfony Workflow), participants, and related story objects.

**StoryObject**: Abstract base class using Doctrine's Single Table Inheritance (JOINED strategy). All story elements inherit from this:
- `Character`: Player characters/NPCs with skills, items, and recruitment proposals
- `Thread`: Story threads connecting multiple characters and events
- `Quest`: Specific quests within the story
- `Event`: Timeline events
- `Relation`: Bidirectional relationships between story objects
- `Faction`: Groups/factions with member characters
- `Item`: In-game items
- `Place`: Locations within the story

**TargetableInterface**: Implemented by entities that support AJAX creation in forms (see TomSelect section below). Requires `getTargetType()` method returning a `TargetType` enum value.

**LarpAwareInterface**: Entities that belong to a specific LARP context. The Larp is passed to forms and used for scoping queries and AJAX entity creation.

### Controller Organization

Controllers are organized by domain under `src/Domain/{DomainName}/Controller/`:
- Public domain: Public-facing pages (`src/Domain/Public/Controller/`)
- StoryObject domain: Story management (`src/Domain/StoryObject/Controller/`)
- Larp domain: LARP operations (`src/Domain/Larp/Controller/`)
- Integration domain: Google APIs (`src/Domain/Integration/Controller/`)
- EventPlanning domain: Event scheduling (`src/Domain/EventPlanning/Controller/`)
- Other domains: Kanban, Incident, Map, Application, Participant

**Legacy organization** (being refactored):
- `src/Controller/Backoffice/`: Organizer/admin interface (requires `ROLE_USER`)
- `src/Controller/API/`: API endpoints

See [`docs/DOMAIN_ARCHITECTURE.md`](docs/DOMAIN_ARCHITECTURE.md) for complete domain organization.

### Security & Access Control

Authentication uses OAuth2 with three providers:
- Google (primary entry point)
- Facebook
- Discord

Custom authenticators: `GoogleAuthenticator`, `FacebookAuthenticator`, `DiscordAuthenticator`

Access control is URL-based:
- `/backoffice/*`: Requires `ROLE_USER`
- `/player/*`: Requires `ROLE_USER`
- `/api/*`: Requires `ROLE_API_USER` or `ROLE_USER`

Voters are located in `src/Security/Voter/Backoffice/` for fine-grained authorization (check subdirectories for specific voters).

### Workflow

LARP entities use Symfony Workflow for status management (`larp_stage_status` state machine):
- Places: DRAFT → WIP → PUBLISHED → INQUIRIES → CONFIRMED → CANCELLED/COMPLETED
- Configuration: `config/packages/workflow.yaml`
- Marking stored via `getMarking()`/`setMarking()` methods on Larp entity

### Frontend Architecture

**Asset Management**: Uses Symfony AssetMapper (not Webpack Encore)
- Configuration: `importmap.php`
- Entrypoint: `assets/app.js`
- Styles: `assets/styles/app.scss` (SASS)

**JavaScript Framework**: Stimulus controllers with Hotwired Stimulus
- Controllers: `assets/controllers/`
- Key controllers:
  - `kanban_controller.js`: Kanban board with SortableJS
  - `wysiwyg_controller.js`: Quill editor with mentions
  - `custom-autocomplete_controller.js`: TomSelect with AJAX entity creation
  - `story_graph_controller.js`: Cytoscape graph visualization

**Key Frontend Libraries**:
- Bootstrap 5.3 for UI
- TomSelect for autocomplete fields
- Quill for WYSIWYG editing
- Cytoscape for graph visualization (story graphs and decision trees)
- SortableJS for drag-and-drop

**Decision Tree Editor** (`decision_tree_controller.js`):
- Visual editor for Quest and Thread branching narratives
- Node types: Start (green ellipse), Decision (yellow diamond), Outcome (cyan rectangle), Reference (gray octagon), End (red ellipse)
- Edge types: Choice (green), Consequence (red dashed), Reference (gray dotted)
- Interactive toolbar: add nodes, connect edges, auto-layout (Dagre algorithm), delete
- Serializes to JSONB format stored in `Quest.decisionTree` / `Thread.decisionTree`
- Routes: `backoffice_larp_story_quest_tree`, `backoffice_larp_story_thread_tree`
- See `docs/DECISION_TREE_SYSTEM.md` for full documentation

### TomSelect AJAX Entity Creation

The system supports creating entities on-the-fly in autocomplete fields via the `FindOrCreateEntityExtension` form extension:

1. Entity must implement `TargetableInterface` with `getTargetType()` method
2. Entity must have `getTitle()` and `setTitle()` methods
3. Entity must have `setLarp()` method
4. Form type must pass `larp` option in `configureOptions()`
5. EntityType field must include:
   ```php
   'autocomplete' => true,
   ```
6. EntityType field can include for the sake of dynamically creating items
   ```php
   'tom_select_options' => [
       'create' => true,
       'persist' => false,
   ]
   ```

The `AutocompleteController` handles AJAX creation requests. See `docs/tom-select_ajax.md` for full details.

### Services

Services are organized by domain under `src/Domain/{DomainName}/Service/`:
- StoryObject domain: Story-related business logic
- Integration domain: Google API integration services
- Larp domain: LARP-specific services
- Application domain: Application matching and voting
- EventPlanning domain: Conflict detection and scheduling
- Infrastructure domain: Core infrastructure services (shared utilities)

**Legacy organization** (being refactored):
- `src/Service/StoryObject/`: Being moved to domain structure
- `src/Service/Integrations/`: Being moved to domain structure
- `src/Service/Larp/`: Being moved to domain structure

### Form System

Forms are organized by domain under `src/Domain/{DomainName}/Form/`:
- `Type/`: Form types for domain entities
- `Filter/`: Filter forms for list views
- `DataTransformer/`: Domain-specific data transformers

**Shared form utilities** (Infrastructure domain):
- `src/Domain/Infrastructure/Form/Extension/`: Form extensions (e.g., `FindOrCreateEntityExtension`)
- `src/Domain/Infrastructure/Form/DataTransformer/`: Shared transformers (JSON, Money)

**Legacy organization** (being refactored):
- `src/Form/`: Original form location

Translation domain: `forms` (see `translations/forms.en.yaml`)

#### Form Filtering Pattern

The application uses `Spiriit\Bundle\FormFilterBundle` for list view filtering. This pattern separates filter logic from controllers.

**Creating a Filter Form**:

1. Create a filter form type in `src/Form/Filter/` that extends `AbstractType`
2. Add filter fields with custom `apply_filter` callbacks for complex logic:

```php
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder->add('character', EntityType::class, [
    'class' => Character::class,
    'required' => false,
    'autocomplete' => true,
    'data_extraction_method' => 'default',
    'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
        if (empty($values['value'])) {
            return null;
        }
        // Access the QueryBuilder and apply custom WHERE clause
        $qb = $filterQuery->getQueryBuilder();
        $qb->andWhere('ch = :filter_character')
            ->setParameter('filter_character', $values['value']);
        return null; // Return null when filter is applied directly
    },
]);
```

3. Configure form options:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'csrf_protection' => false,
        'validation_groups' => ['filtering'],
        'method' => 'GET',
        'translation_domain' => 'forms',
        'larp' => null, // Pass LARP context if needed
    ]);
}
```

**Using Filters in Controllers**:

1. Create the filter form and handle the request:
```php
$filterForm = $this->createForm(LarpApplicationChoiceFilterType::class, null, ['larp' => $larp]);
$filterForm->handleRequest($request);
```

2. Build your base query:
```php
$qb = $repository->createQueryBuilder('c')
    ->join('c.application', 'a')
    ->join('c.character', 'ch')
    ->andWhere('a.larp = :larp')
    ->setParameter('larp', $larp);
```

3. Apply filters using `FilterBuilderUpdaterInterface` (injected in `BaseController`):
```php
$this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
```

4. Apply sorting separately (sorting is NOT part of the filter pattern):
```php
$sortBy = $request->query->get('sortBy', 'character');
$sortOrder = $request->query->get('sortOrder', 'ASC');

switch ($sortBy) {
    case 'character':
        $qb->orderBy('ch.title', $sortOrder);
        break;
    case 'priority':
        $qb->orderBy('c.priority', $sortOrder);
        break;
}
```

5. Paginate the results:
```php
$pagination = $this->getPagination($qb, $request);
```

**Sorting Fields**:

For sort controls in filter forms, use twig since they're UI controls, not filters:

```php
{% include 'includes/sort_th.html.twig' with { field: 'name', label: 'common.name'|trans } %}
```

**Examples**: See `FactionController::list()` for complete implementations.

#### Backoffice List View Pattern

Backoffice list pages follow a consistent template pattern for displaying filtered, sortable data tables.

**Template Structure** (see `templates/backoffice/larp/tag/list.html.twig:15-57`):

```twig
<div class="card mt-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ 'larp.tag.list'|trans }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ path('backoffice_larp_story_tag_modify', { larp: larp.id }) }}"
                   class="btn btn-success">
                    {{ 'common.create'|trans }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        {# Include filter form #}
        {% include 'includes/filter_form.html.twig' with { form: filterForm } %}

        {# Show table if data exists, otherwise show empty message #}
        {% if tags is not empty %}
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            {# Sortable column header #}
                            {% include 'includes/sort_th.html.twig' with {
                                field: 'name',
                                label: 'common.name'|trans
                            } %}
                            <th>{{ 'common.description'|trans }}</th>
                            <th>{{ 'common.actions'|trans }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for tag in tags %}
                            <tr>
                                <td>
                                    {# Link to edit page on title #}
                                    <a href="{{ path('backoffice_larp_story_tag_modify', {
                                        larp: larp.id, tag: tag.id
                                    }) }}">
                                        {{ tag.title }}
                                    </a>
                                </td>
                                <td>{{ tag.description|sanitize_html|default('-') }}</td>
                                <td>
                                    {# Delete button with modal #}
                                    <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-item-id="{{ tag.id }}"
                                            data-item-name="{{ tag.title }}"
                                            data-delete-url="{{ path('backoffice_larp_story_tag_delete', {
                                                larp: larp.id, tag: tag.id
                                            }) }}">
                                        {{ 'common.delete'|trans }}
                                    </button>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        {% else %}
            <p class="text-muted">{{ 'common.empty_list'|trans }}</p>
        {% endif %}
    </div>
</div>
```

**Key Components**:

1. **Card Layout**:
   - Header with page title and "Create" button
   - Body with `p-0` class (no padding, filter/table handle their own padding)

2. **Filter Form Include**:
   - `{% include 'includes/filter_form.html.twig' with { form: filterForm } %}`
   - Renders filter fields in a row with submit/clear buttons
   - Optionally includes "Save Filter" component when `larp` is passed

3. **Conditional Data Display**:
   - Check `{% if items is not empty %}` before rendering table
   - Show `{{ 'common.empty_list'|trans }}` message when no data

4. **Table Styling**:
   - `table-responsive` wrapper for mobile scrolling
   - `table table-striped table-hover mb-0` classes
   - `table-light` class on `<thead>`

5. **Sortable Headers**:
   - Include `includes/sort_th.html.twig` for sortable columns
   - Displays sort direction icon (`bi-caret-up-fill` / `bi-caret-down-fill`)
   - Maintains filter parameters in sort URLs

6. **Row Actions**:
   - Title links to edit page: `path('..._modify', { larp: larp.id, item: item.id })`
   - Delete button triggers Bootstrap modal with `data-bs-toggle="modal"`
   - Pass item data via `data-*` attributes for JavaScript handling

7. **Empty State**:
   - Simple text message: `<p class="text-muted">{{ 'common.empty_list'|trans }}</p>`

**Sorting Implementation**:

The `includes/sort_th.html.twig` template handles sortable column headers:
- Accepts `field` (sort parameter) and `label` (display text)
- Preserves all query parameters when toggling sort
- Shows current sort direction with Bootstrap icons
- Toggles between `asc`/`desc` on click

**Controller Requirements**:
- Pass `filterForm` (filter form view)
- Pass collection variable (e.g., `tags`, `characters`)
- Handle `?sort=field&dir=asc` query parameters for sorting

### Doctrine Extensions

Uses Gedmo extensions (configured in `config/packages/stof_doctrine_extensions.yaml`):
- Timestampable: Auto-managed `createdAt`/`updatedAt`
- Loggable: Version tracking on StoryObject (logs stored in `StoryObjectLogEntry`)
- Sluggable: Auto-generated slugs (e.g., Larp title → slug)

Custom DQL functions for PostgreSQL JSON operations:
- `JSON_GET_TEXT`, `JSONB_EXISTS`, `JSONB_EXISTS_ANY`, `JSONB_CONTAINS`

## Development Fixtures

Development fixtures available via `SeedLarpFixturesCommand` for testing. Located in `src/DataFixtures/Dev/`.

## Environment Variables

Copy `.env` to `.env.local` and configure:
- Database credentials (PostgreSQL)
- OAuth client IDs/secrets (Google, Facebook, Discord)
- Google API credentials for integrations
- Google service account JSON path
- ReCAPTCHA keys

## Testing

Run tests with PHPUnit:
```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

Test database uses suffix `_test` (configured in `config/packages/doctrine.yaml`).

## Code Quality Standards

- **PHP Version**: 8.2+
- **Coding Standard**: PSR-12 (enforced by ECS)
- **Static Analysis**: PHPStan with strict rules
- **Rector**: Automated refactoring to PHP 8.2 standards
- Always run quality tools before committing changes


**Documentation**: 
see `docs/*` for full requirements and architecture.
