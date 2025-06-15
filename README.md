# LARPilot

This project uses Symfony and requires PHP, Composer, Node.js and Yarn.

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

