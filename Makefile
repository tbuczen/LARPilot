EXEC_OPT = -T
APP_CONTAINER = php
EXEC_PHP = $(DOCKER_COMPOSE) exec ${EXEC_OPT} ${APP_CONTAINER}

ifeq ($(OS),Windows_NT)
	SHELL := pwsh.exe
	.SHELLFLAGS := -NoProfile -Command
	DOCKER := docker
	COMPOSE := docker compose
	SHC := bash -lc
else
	SHELL := /bin/bash
	DOCKER := docker
	COMPOSE := docker compose
	SHC := bash -lc
endif

.PHONY: up build install migrate assets dev start stop clean logs
.PHONY: test test-unit test-filter lint lint-fix stan ecs ecs-fix qa
.PHONY: db-wait db-create db-drop db-migrate db-reset db-status db-shell

# Wait until Postgres is ready (inside compose network)
db-wait:
	@echo "Waiting for Postgres..."
	@for i in {1..60}; do \
	  docker compose exec -T postgres sh -lc 'pg_isready -U $$POSTGRES_USER' && exit 0; \
	  sleep 1; \
	done; \
	echo "Postgres did not become ready in time" && exit 1

# Create DB if not exists using Doctrine (uses DATABASE_URL from env/compose)
db-create:
	docker compose exec -T php bash -lc "php bin/console doctrine:database:create --if-not-exists"

# Run migrations
db-migrate:
	docker compose exec -T php bash -lc "php bin/console doctrine:migrations:migrate --no-interaction"

# Drop and recreate from scratch
db-reset:
	docker compose exec -T php bash -lc "php bin/console doctrine:database:drop --force || true"
	make db-create
	make db-migrate

# Show migration status
db-status:
	docker compose exec -T php bash -lc "php bin/console doctrine:migrations:status"

# psql shell into service DB
db-shell:
	docker compose exec

up:
	docker compose up -d --build

start:
	docker compose up

down:
	docker compose down

build: up install migrate assets

cli:
	docker compose exec -ti php bash

install:
	docker compose exec -T php bash -lc 'composer install --no-interaction --prefer-dist'

migrate:
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console doctrine:database:create --if-not-exists"
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console doctrine:migrations:migrate --no-interaction"

cc:
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console ca:cl --env=dev"
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console ca:wa --env=dev"
	docker compose exec -T php bash -lc "chmod 777 -R ./var/cache"


assets:
	docker compose exec -T php bash -lc "rm -rf public/assets/*"
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console importmap:install"
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console sass:build || true"
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 bin/console asset-map:compile"
	docker compose exec -T php bash -lc "composer dump-autoload"

local-fixtures:
	docker compose exec -T php bash -lc "APP_ENV=dev php bin/console doctrine:fixtures:load --no-interaction --env=dev"

# QA

prepare-test-db:
	docker compose exec -T php bash -lc "php bin/console doctrine:database:create --if-not-exists --env=test"
	docker compose exec -T php bash -lc "php bin/console doctrine:migrations:migrate --no-interaction --env=test"

test:
	docker compose exec -T php bash -lc "APP_ENV=test php vendor/bin/phpunit -c phpunit.xml.dist --colors=always"

# Run a single test class or method: make test-filter FILTER=BackofficeAccessHappyPathTest
test-filter:
	@if [ -z "$(FILTER)" ]; then echo "Usage: make test-filter FILTER=Pattern"; exit 1; fi
	docker compose exec -T php vendor/bin/phpunit -c phpunit.xml.dist --colors=always --filter "$(FILTER)"

# Optional alias for unit tests if you separate suites later
test-unit: test

# Static analysis (PHPStan)
stan:
	docker compose exec -T php bash -lc "XDEBUG_MODE=off php -d memory_limit=-1 vendor/bin/phpstan analyse -c phpstan.neon"

# Lint (code style check)
ecs:
	docker compose exec -T php vendor/bin/ecs check

# Lint fix (apply code style)
ecs-fix:
	docker compose exec -T php vendor/bin/ecs check --fix

# Rector dry-run
rector:
	docker compose exec -T php vendor/bin/rector process --dry-run

rector-fix:
	docker compose exec -T php vendor/bin/rector process

# Combined quality gate
qa: ecs stan test