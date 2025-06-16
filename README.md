# LARPilot

This project uses Symfony and requires PHP, Composer, Node.js and Yarn.

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
  nad rekwizytami i scenografią.
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

## Prerequisites

- **PHP**: 8.2 or higher with required extensions (`ctype`, `iconv`).
- **Composer**: for PHP dependency management.
- **Node.js**: 18+ with Yarn installed for frontend assets.
- **PostgreSQL**: database service for local development.

## Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Install JavaScript dependencies:
   ```bash
   yarn install
   ```
3. Configure your environment variables. Copy `.env` to `.env.local` and update database credentials and API keys as needed. Example variables are included in `.env`.
4. Run database migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

## Quality tools

- **Code style**: `vendor/bin/ecs check`
- **Static analysis**: `vendor/bin/phpstan analyse -c phpstan.neon`
- **Tests**: `vendor/bin/phpunit -c phpunit.xml.dist`

Make sure these commands run successfully before committing changes.

## Development

Start the Symfony web server:
```bash
symfony server:start
```

Build frontend assets:
```bash
# Development build
yarn dev

# Hot reload
yarn dev-server

# Production build
yarn build
```

