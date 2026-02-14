.PHONY: up down restart build install test lint fresh seed shell-api shell-frontend

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build

install:
	docker compose run --rm api composer install
	docker compose run --rm frontend pnpm install

test:
	docker compose run --rm api ./vendor/bin/pest
	docker compose run --rm frontend pnpm test

test-be:
	docker compose run --rm api ./vendor/bin/pest

test-fe:
	docker compose run --rm frontend pnpm vitest run

lint:
	docker compose run --rm api ./vendor/bin/pint --test
	docker compose run --rm api ./vendor/bin/phpstan analyse
	docker compose run --rm frontend pnpm lint

fresh:
	docker compose run --rm api php artisan migrate:fresh

seed:
	docker compose run --rm api php artisan db:seed

shell-api:
	docker compose exec api bash

shell-frontend:
	docker compose exec frontend sh
