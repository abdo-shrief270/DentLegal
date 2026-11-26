# Local development (PROJECT1_PLAN.md S0.1). Needs Docker with Compose v2.
.PHONY: up down reset migrate seed test logs ps

up: ## start the stack, migrate and seed the synthetic demo data
	docker compose up -d --build --wait
	docker compose exec -T api php artisan migrate --seed --force
	docker compose exec -T api php artisan db:seed --class=DemoSeeder --force
	@echo "API http://localhost:8000 · ai-svc http://localhost:8001/v1/health · Mailpit http://localhost:8025"

down:
	docker compose down

reset: ## drop the database volume too
	docker compose down -v

migrate:
	docker compose exec -T api php artisan migrate

seed:
	docker compose exec -T api php artisan db:seed --class=DemoSeeder --force

test: ## backend and ai-svc tests against the stack's Postgres
	docker compose exec -e DB_DATABASE=dentlegal_test api php artisan test
	docker compose exec -e AI_TEST_DATABASE_URL=postgresql://dentlegal:dentlegal@postgres:5432/dentlegal_test ai pytest

logs:
	docker compose logs -f api horizon ai

ps:
	docker compose ps
