.PHONY: up upwc down restart build install test lint fresh seed shell-api shell-frontend deploy tinker

up:
	docker compose up -d

upwc:
	docker compose stop frontend
	docker compose rm -f frontend
	docker volume rm -f $$(docker volume ls -q | grep frontend-next) 2>/dev/null || true
	docker compose up -d frontend
	docker compose run --rm --user root frontend chown -R 1000:1000 /app/.next
	docker compose restart frontend

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build

install:
	docker compose run --rm api composer install
	docker compose run --rm frontend pnpm install

deploy:
	git pull origin main
	docker compose build
	docker compose up -d
	docker compose run --rm api composer install --no-dev --optimize-autoloader
	docker compose run --rm api php artisan migrate --force
	docker compose run --rm api php artisan storage:link
	docker compose run --rm api php artisan config:cache
	docker compose run --rm api php artisan route:cache
	docker compose run --rm api php artisan view:cache
	docker compose run --rm api php artisan event:cache
	docker compose run --rm frontend pnpm install
	docker compose run --rm frontend pnpm build
	docker compose restart api frontend queue-worker scheduler

test:
	docker compose run --rm api ./vendor/bin/pest
	docker compose run --rm frontend pnpm test

test-be:
	docker compose run --rm api ./vendor/bin/pest

test-fe:
	docker compose run --rm frontend pnpm vitest run

lint:
	docker compose run --rm api ./vendor/bin/pint --test
	docker compose run --rm api ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M
	docker compose run --rm frontend pnpm lint

fresh:
	docker compose run --rm api php artisan migrate:fresh

seed:
	docker compose run --rm api php artisan db:seed

shell-api:
	docker compose exec api bash

shell-frontend:
	docker compose exec frontend sh

tinker:
	docker compose run --rm api php artisan tinker

user:
	docker compose run --rm api php artisan users:create