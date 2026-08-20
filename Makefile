.PHONY: setup dev prod down build logs test lint analyse ci

setup:
	cp -n .env.example .env || true
	docker compose run --rm php composer install
	docker compose run --rm php php bin/console doctrine:migrations:migrate --no-interaction

dev:
	docker compose up -d --build

prod:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f

test:
	composer test

lint:
	composer lint

analyse:
	composer analyse

ci:
	composer ci
