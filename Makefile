# =============================================================================
# Koomky - Makefile
# =============================================================================

.PHONY: help up down restart logs shell shell-fe build install fresh test lint lint-fix test-coverage db-migrate db-seed db-reset db-wipe health

# Default target
.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
# Help
# -----------------------------------------------------------------------------
help: ## Show this help message
	@echo ''
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*?##/ {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ''

# -----------------------------------------------------------------------------
# Docker Management
# -----------------------------------------------------------------------------
up: ## Start all Docker containers
	docker compose up -d
	@echo ""
	@echo "Services started:"
	@echo "  - http://localhost          (Nuxt.js Frontend)"
	@echo "  - http://localhost/api/v1   (Laravel API)"
	@echo "  - http://localhost:7700    (Meilisearch Admin)"
	@echo "  - http://localhost:8025    (Mailpit Email Testing)"
	@echo ""
	@echo "Run 'make logs' to see container logs."

down: ## Stop all Docker containers
	docker compose down
	@echo "All containers stopped."

restart: down up ## Restart all Docker containers

logs: ## Show logs from all containers
	docker compose logs -f

logs-api: ## Show API container logs
	docker compose logs -f api

logs-frontend: ## Show Frontend container logs
	docker compose logs -f frontend

logs-db: ## Show PostgreSQL container logs
	docker compose logs -f postgres

shell: ## SSH into API container
	docker compose exec api bash

shell-fe: ## SSH into Frontend container
	docker compose exec frontend sh

# -----------------------------------------------------------------------------
# Build & Installation
# -----------------------------------------------------------------------------
build: ## Build all Docker images
	docker compose build

install: ## Install all dependencies (PHP + Node)
	@echo "Installing backend dependencies..."
	docker compose exec api composer install
	@echo "Installing frontend dependencies..."
	docker compose exec frontend pnpm install
	@echo "Dependencies installed."

fresh: down build up ## Reset everything: remove containers, rebuild, and start
	@echo ""
	@echo "Running fresh install..."
	@$(MAKE) down
	@$(MAKE) up
	@echo "Waiting for containers..."
	@sleep 10
	@$(MAKE) db-migrate
	@$(MAKE) db-seed
	@echo ""
	@echo "Fresh installation complete!"

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------
db-migrate: ## Run database migrations
	docker compose exec api php artisan migrate

db-migrate-fresh: ## Drop all tables and re-run migrations
	docker compose exec api php artisan migrate:fresh

db-seed: ## Seed the database with test data
	docker compose exec api php artisan db:seed

db-reset: ## Rollback and re-run all migrations
	docker compose exec api php artisan migrate:refresh --seed

db-wipe: ## Drop all tables
	docker compose exec api php artisan db:wipe

db-shell: ## Open PostgreSQL shell
	docker compose exec postgres psql -U koomky -d koomky

tinker: ## Open Laravel Tinker REPL
	docker compose exec api php artisan tinker

# -----------------------------------------------------------------------------
# Testing
# -----------------------------------------------------------------------------
test: ## Run all tests (backend + frontend)
	@echo "Running backend tests..."
	docker compose exec api pest --coverage
	@echo ""
	@echo "Running frontend tests..."
	docker compose exec frontend pnpm test
	@echo ""
	@echo "Tests complete."

test-be: ## Run backend tests only
	docker compose exec api pest

test-be-coverage: ## Run backend tests with coverage
	docker compose exec api pest --coverage --min=80

test-fe: ## Run frontend tests only
	docker compose exec frontend pnpm test

test-fe-coverage: ## Run frontend tests with coverage
	docker compose exec frontend pnpm test:coverage

test-e2e: ## Run E2E tests with Playwright
	docker compose exec frontend pnpm test:e2e

# -----------------------------------------------------------------------------
# Code Quality
# -----------------------------------------------------------------------------
lint: ## Run all linting (PHP + TypeScript)
	@echo "Linting backend..."
	docker compose exec api ./vendor/bin/pint --test
	docker compose exec api ./vendor/bin/phpstan analyse --memory-limit=2G
	@echo ""
	@echo "Linting frontend..."
	docker compose exec frontend pnpm lint
	@echo ""
	@echo "All linting complete."

lint-be: ## Run backend linting
	docker compose exec api ./vendor/bin/pint --test
	docker compose exec api ./vendor/bin/phpstan analyse --memory-limit=2G

lint-be-fix: ## Auto-fix backend code style issues
	docker compose exec api ./vendor/bin/pint

lint-fe: ## Run frontend linting
	docker compose exec frontend pnpm lint

lint-fe-fix: ## Auto-fix frontend code style issues
	docker compose exec frontend pnpm lint:fix

format: ## Run all formatters (fix mode)
	docker compose exec api ./vendor/bin/pint
	docker compose exec frontend pnpm format

# -----------------------------------------------------------------------------
# Cache & Queue
# -----------------------------------------------------------------------------
cache-clear: ## Clear all application caches
	docker compose exec api php artisan cache:clear
	docker compose exec api php artisan config:clear
	docker compose exec api php artisan route:clear
	docker compose exec api php artisan view:clear

queue-work: ## Run queue worker in foreground
	docker compose exec api php artisan queue:work --timeout=300

queue-restart: ## Restart queue worker
	docker compose restart queue-worker

schedule-work: ## Run scheduler in foreground
	docker compose exec api php artisan schedule:work

# -----------------------------------------------------------------------------
# Maintenance
# -----------------------------------------------------------------------------
optimize: ## Optimize the application
	docker compose exec api php artisan optimize
	docker compose exec api php artisan view:cache
	docker compose exec api php artisan config:cache

storage-link: ## Create the symbolic link for storage
	docker compose exec api php artisan storage:link

# -----------------------------------------------------------------------------
# Health & Status
# -----------------------------------------------------------------------------
health: ## Check health status of all services
	@echo "Checking service health..."
	@echo ""
	@docker compose ps
	@echo ""
	@curl -s http://localhost/api/v1/health || echo "API health check failed"
	@echo ""

ps: ## Show running containers
	docker compose ps

top: ## Show running processes in containers
	docker compose top

# -----------------------------------------------------------------------------
# Production (for later use)
# -----------------------------------------------------------------------------
deploy: ## Deploy to production (requires SSH access)
	@echo "Deployment not configured yet. See docs/deployment.md"

# -----------------------------------------------------------------------------
# Development helpers
# -----------------------------------------------------------------------------
cc: cache-clear ## Shortcut for cache:clear
m: db-migrate ## Shortcut for db-migrate
s: db-seed ## Shortcut for db-seed
t: test ## Shortcut for test
