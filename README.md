# LARPilot

LARPilot is a comprehensive platform for managing Live Action Role-Playing (LARP) events. The system integrates tools for both players and organizers with Google services (Sheets, Docs, Calendar) integration.

## Table of Contents

- [Project Overview (Polish)](#opis-projektu)
- [Documentation](#documentation)
- [Technical Architecture](#technical-architecture)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Quality Tools](#quality-tools)
- [Development](#development)
- [Contributing](#contributing)

## Opis projektu

LARPilot jest platformą do zarządzania grami terenowymi (LARP). System łączy w
sobie narzędzia dla graczy oraz organizatorów i integruje się z usługami
Google (Sheets, Docs, Calendar). Najważniejsze moduły obejmują:

- **Panel dla graczy** – rejestracja na larpy, przegląd własnych wydarzeń oraz
  dostęp do kart postaci i udostępnionych informacji.
- **Panel organizatora** – logowanie z użyciem kont Google/Facebook,
  definiowanie ról organizacyjnych (główny fabularzysta, mistrz gry,
  crafter itd.), zarządzanie zgłoszeniami graczy oraz przypisywanie ich do
  wakatów.
- **Moduł fabularny** – przejrzyste tworzenie wątków i postaci wraz z ich
  powiązaniami. Umożliwia wizualizację relacji, dodawanie zadań oraz
  wymagań dotyczących NPC i scenografii.
- **Moduł crafterski** – listy zadań w stylu kanban do koordynacji prac
  nad rekwizytami i scenografią. Tablice mogą również służyć do
  monitorowania innych zadań organizacyjnych, np. planowania cateringu,
  organizacji terenu, umów, etc.
- **Moduł zaufania** – bezpieczne zgłaszanie incydentów podczas gry z opcją
  anonimowości, mediacji i eskalacji zgodnie z procedurą opisującą kody
  uczestników oraz śledzenie statusu sprawy.
- **Moduł zapisów** – obsługa zgłoszeń graczy, konflikty między graczami,
  dynamiczne ceny biletów i możliwość zwolnienia roli w przypadku rezygnacji.
- **Moduł księgowości** – ewidencja kosztów (wynajem terenu, scenografia,
  gastronomia) i przychodów z biletów.

Te funkcje odpowiadają na potrzeby organizatorów, którzy oczekują jednego
miejsca do planowania wątków, zarządzania kartami postaci i komunikacji z
uczestnikami, a także narzędzia do raportowania i obsługi incydentów.

## Documentation

### User Guides

- [Character Allocation System](docs/CHARACTER_ALLOCATION_SYSTEM.md) - Player application and character assignment workflow
- [Decision Tree System](docs/DECISION_TREE_SYSTEM.md) - Interactive branching narrative editor for quests and threads
- [Event Planning System](docs/EVENT_PLANNING_SYSTEM.md) - Event scheduling, resource booking, and conflict detection
- [Feedback System User Guide](docs/FEEDBACK_SYSTEM_USER_GUIDE.md) - How to provide and manage feedback

### Technical Documentation

- [Domain Architecture](docs/DOMAIN_ARCHITECTURE.md) - **Start here** - Complete domain structure and organization
- [TomSelect AJAX Entity Creation](docs/technical/tom-select_ajax.md) - Dynamic entity creation in autocomplete fields
- [Character Allocation Technical](docs/technical/CHARACTER_ALLOCATION_TECHNICAL.md) - Implementation details of matching algorithm
- [Decision Tree Implementation](docs/DECISION_TREE_IMPLEMENTATION_SUMMARY.md) - Technical overview of decision tree editor
- [Event Planning Technical](docs/technical/EVENT_PLANNER_POC_README.md) - FullCalendar integration and conflict detection
- [Feedback System Technical](docs/technical/FEEDBACK_SYSTEM_TECHNICAL.md) - Feedback collection architecture
- [Quill Editor Mentions](docs/technical/quill_mention.md) - @mentions implementation in WYSIWYG editor
- [LARP Workflow](docs/technical/larp_workflow.md) - State machine for LARP lifecycle
- [Security Configuration](docs/technical/larp_backoffice_security_configuration.md) - Backoffice access control
- [DTO Pagination Guide](docs/DTO_PAGINATION_GUIDE.md) - Paginated data transfer patterns

## Technical Architecture

LARPilot follows a **Domain-Driven Design (DDD)** approach with modular architecture. The application is organized into self-contained domains under `src/Domain/`. See [Domain Architecture](docs/DOMAIN_ARCHITECTURE.md) for complete details.

### Core Domains

- **Infrastructure** - Shared kernel with cross-cutting concerns, base classes, and shared utilities
- **Account** - User authentication and profile management (OAuth: Google, Facebook, Discord)
- **Public** - Public-facing LARP discovery and character sheet viewer
- **Larp** - Core LARP event lifecycle with workflow-based status management (DRAFT → WIP → PUBLISHED → CONFIRMED)

### Story Management

- **StoryObject** - Story elements (Character, Thread, Quest, Event, Faction, Item, Place, Relation) with graph visualization and decision trees
- **StoryMarketplace** - Quest/thread recruitment system for story writers
- **Application** - Player application submission and character matching algorithm

### Operations

- **Participant** - LARP participant roster (players, GMs, staff) with invitation codes
- **Kanban** - Task management with drag-and-drop boards for crafting and logistics
- **Incident** - Safety incident reporting and tracking system
- **EventPlanning** - Event scheduling with FullCalendar, resource booking, and conflict detection
- **Map** - Interactive game maps with location pins

### Integration

- **Integration** - Google Sheets character import, Google Docs sync, Google Calendar events

### Tech Stack

- **Backend**: PHP 8.2+, Symfony 7.2, Doctrine ORM, PostgreSQL
- **Frontend**: Stimulus controllers, Bootstrap 5.3, TomSelect, Quill editor, Cytoscape graph, FullCalendar
- **Asset Management**: Symfony AssetMapper (not Webpack Encore)
- **Testing**: PHPUnit, PHPStan (static analysis), ECS (code style)

## Prerequisites

- **PHP**: 8.2 or higher with required extensions (`ctype`, `iconv`).
- **Composer**: for PHP dependency management.
- **Node.js**: 18+ with Yarn installed for frontend assets.
- **PostgreSQL**: database service for local development.
- **Docker**: recommended for local development (see `docker-compose.yml`)

## Setup

### Using Docker (Recommended)

1. Install dependencies:
   ```bash
   make install
   ```

2. Set up JavaScript environment:
   ```bash
   make assets
   ```

3. Configure environment variables:
   - Copy `.env` to `.env.local`
   - Update database credentials, OAuth client IDs/secrets, and Google API credentials

4. Run database migrations:
   ```bash
   make migrate
   ```

5. Start the development server:
   ```bash
   make start
   ```

### Manual Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Set up JavaScript environment:
   ```bash
   php bin/console importmap:install
   php bin/console sass:build
   php bin/console asset-map:compile
   ```

3. Configure `.env.local` with your credentials

4. Run migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

5. Start Symfony server:
   ```bash
   symfony server:start
   ```

## Quality Tools

LARPilot enforces strict code quality standards:

```bash
# Code style (PSR-12 + import cleanup)
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

**Make sure these commands run successfully before committing changes.**

## Development

### Development Workflow

1. Create a feature branch from `main`
2. Make your changes following SOLID, KISS, and YAGNI principles
3. Run quality tools (`make ecs-fix`, `make stan`, `make test`)
4. Commit with descriptive messages
5. Create a pull request

### Development Fixtures

Load development fixtures for testing:

```bash
php bin/console doctrine:fixtures:load
```

Fixtures are located in `src/DataFixtures/Dev/` and include sample LARPs, characters, and story objects.

## Contributing

We welcome contributions! Here's how to get involved:

### Reporting Issues

Found a bug or have a feature request?

**→ [Submit an issue on GitHub](https://github.com/tbuczen/LARPilot/issues/new)**

When reporting issues, please include:
- Clear description of the problem or feature request
- Steps to reproduce (for bugs)
- Expected vs. actual behavior
- Screenshots (if applicable)
- Your environment (PHP version, browser, etc.)

### Code Contributions

1. Check existing [GitHub Issues](https://github.com/tbuczen/LARPilot/issues) for open tasks
2. Comment on an issue to claim it or discuss your approach
3. Fork the repository and create a feature branch
4. Follow the code style and architecture patterns (see [Domain Architecture](docs/DOMAIN_ARCHITECTURE.md))
5. Write tests for new functionality
6. Ensure all quality tools pass
7. Submit a pull request with a clear description

### Coding Standards

- **PHP**: PSR-12 coding standard
- **Architecture**: Domain-Driven Design principles
- **Principles**: SOLID, KISS, YAGNI
- **Testing**: PHPUnit for unit and functional tests
- **Documentation**: Update docs when adding features

## License

This project is proprietary software. All rights reserved.

## Support

For questions and support:
- Open an issue on [GitHub](https://github.com/tbuczen/LARPilot/issues)
- Check the [documentation](docs/)
- Review existing issues for similar problems
