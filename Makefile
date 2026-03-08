DOCKER_UID := $(shell id -u)
DOCKER_GID := $(shell id -g)
DOCKER_COMPOSE ?= $(shell if docker compose version > /dev/null 2>&1; then echo "docker compose"; else echo "docker-compose"; fi)

PROD_HOST := gerald@163.172.110.246
PROD_DIR  := /home/gerald/web/crm

.PHONY: up upwc down restart build install test lint fresh seed shell-api shell-frontend deploy tinker import-prospects go-live docs-diagrams docs-screenshots

up:
	$(DOCKER_COMPOSE) up -d --build

upwc:
	$(DOCKER_COMPOSE) stop frontend
	$(DOCKER_COMPOSE) rm -f frontend
	docker volume rm -f $$(docker volume ls -q | grep frontend-next) 2>/dev/null || true
	$(DOCKER_COMPOSE) up -d frontend
	$(DOCKER_COMPOSE) run --rm --user root frontend chown -R 1000:1000 /app/.next
	$(DOCKER_COMPOSE) restart frontend

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) restart

build:
	$(DOCKER_COMPOSE) build
	docker tag admin_koomky-frontend:latest admin_koomky_frontend:latest 2>/dev/null || true
	docker tag admin_koomky-api:latest admin_koomky_api:latest 2>/dev/null || true
	docker tag admin_koomky-queue-worker:latest admin_koomky_queue-worker:latest 2>/dev/null || true
	docker tag admin_koomky-scheduler:latest admin_koomky_scheduler:latest 2>/dev/null || true

install:
	$(DOCKER_COMPOSE) run --rm api composer install
	$(DOCKER_COMPOSE) run --rm frontend pnpm install

deploy:
	git pull origin main
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	$(DOCKER_COMPOSE) run --rm api composer install --no-dev --optimize-autoloader
	$(DOCKER_COMPOSE) run --rm api php artisan migrate --force
	$(DOCKER_COMPOSE) run --rm api php artisan storage:link
	$(DOCKER_COMPOSE) run --rm api php artisan config:cache
	$(DOCKER_COMPOSE) run --rm api php artisan route:cache
	$(DOCKER_COMPOSE) run --rm api php artisan view:cache
	$(DOCKER_COMPOSE) run --rm api php artisan event:cache
	$(DOCKER_COMPOSE) run --rm frontend pnpm install
	$(DOCKER_COMPOSE) run --rm frontend pnpm build
	$(DOCKER_COMPOSE) restart api frontend queue-worker scheduler

go-live:
	@echo "→ Syncing to $(PROD_HOST):$(PROD_DIR) ..."
	rsync -avz --delete \
		--exclude='.git/' \
		--exclude='backend/vendor/' \
		--exclude='backend/storage/logs/*.log' \
		--exclude='backend/.env' \
		--exclude='frontend/node_modules/' \
		--exclude='frontend/.next/' \
		--exclude='frontend/.env*' \
		--exclude='data/koomky/' \
		--exclude='data/prospects/*.xlsx' \
		--exclude='data/prospects/imported/' \
		--exclude='.env' \
		-e "ssh -o StrictHostKeyChecking=no" \
		. $(PROD_HOST):$(PROD_DIR)/
	@echo "→ Running post-deploy on server ..."
	ssh -o StrictHostKeyChecking=no $(PROD_HOST) 'cd $(PROD_DIR) && set -a && [ -f .env ] && . ./.env && set +a && export MCP_KOOMKY_URL=$${MCP_KOOMKY_URL:-http://api:8000} DOCKER_UID=$$(id -u) DOCKER_GID=$$(id -g) && \
		mkdir -p ../data/koomky/grafana 2>/dev/null || true && \
		docker-compose run --rm api composer install --no-dev --optimize-autoloader && \
		docker-compose run --rm api php artisan migrate --force && \
		docker-compose run --rm api php artisan config:cache && \
		docker-compose run --rm api php artisan route:cache && \
		docker-compose run --rm api php artisan view:cache && \
		docker-compose run --rm api php artisan event:cache && \
		docker-compose build frontend && \
		docker-compose run --rm --user root frontend pnpm install --frozen-lockfile && \
		docker-compose run --rm --user root frontend pnpm build && \
		docker-compose run --rm --user root frontend chown -R $$(id -u):$$(id -g) /app/node_modules /app/.next && \
		docker-compose up -d --remove-orphans'
	@echo "✓ Deployment complete."

test:
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pest
	$(DOCKER_COMPOSE) run --rm frontend pnpm test

test-be:
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pest

test-fe:
	$(DOCKER_COMPOSE) run --rm frontend pnpm vitest run

lint:
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pint --test
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M
	$(DOCKER_COMPOSE) run --rm frontend pnpm lint

fresh:
	$(DOCKER_COMPOSE) run --rm api php artisan migrate:fresh

seed:
	$(DOCKER_COMPOSE) run --rm api php artisan db:seed

shell-api:
	$(DOCKER_COMPOSE) exec api bash

shell-frontend:
	$(DOCKER_COMPOSE) exec frontend sh

tinker:
	$(DOCKER_COMPOSE) run --rm api php artisan tinker

user:
	$(DOCKER_COMPOSE) run --rm api php artisan users:create

import-prospects:
	$(DOCKER_COMPOSE) run --rm api php artisan leads:import-xlsx $(ARGS)

docs-diagrams:
	$(DOCKER_COMPOSE) run --rm frontend sh -lc "pnpm install --frozen-lockfile && pnpm docs:diagrams"

docs-screenshots:
	$(DOCKER_COMPOSE) up -d api nginx frontend
	$(DOCKER_COMPOSE) exec -T \
		-e DOCS_SCREENSHOT_EMAIL \
		-e DOCS_SCREENSHOT_PASSWORD \
		-e DOCS_SCREENSHOT_BASE_URL=$${DOCS_SCREENSHOT_BASE_URL:-http://nginx} \
		-e PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=/usr/bin/chromium \
		frontend sh -lc "apk add --no-cache chromium >/dev/null && pnpm install --frozen-lockfile && until wget -q -O /dev/null $${DOCS_SCREENSHOT_BASE_URL}/auth/login; do sleep 2; done && pnpm docs:screenshots"
