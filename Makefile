.PHONY: dev prod down build logs

dev:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build

prod:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

down:
	docker compose down

logs:
	docker compose logs -f

deploy:
	git pull
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

rebuild:
	docker compose down
	docker compose build --no-cache
	docker compose up -d
