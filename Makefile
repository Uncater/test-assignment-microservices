# Symfony Docker Project Makefile
# Manages containers, tests, and integration testing for the event-driven microservices architecture

.PHONY: help start stop restart build logs clean test test-unit test-integration install setup status health

# Default target
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
RED := \033[31m
NC := \033[0m

##@ Container Management

start: ## Start all containers
	@echo "$(BLUE)Starting all containers...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✅ All containers started$(NC)"
	@$(MAKE) --no-print-directory wait-for-services

stop: ## Stop all containers
	@echo "$(BLUE)Stopping all containers...$(NC)"
	docker-compose down
	@echo "$(GREEN)✅ All containers stopped$(NC)"

restart: stop start ## Restart all containers

build: ## Build all containers
	@echo "$(BLUE)Building all containers...$(NC)"
	docker-compose build --no-cache
	@echo "$(GREEN)✅ All containers built$(NC)"

logs: ## Show logs for all services
	docker-compose logs -f

logs-order: ## Show logs for order-service
	docker-compose logs -f order-service

logs-product: ## Show logs for product-service
	docker-compose logs -f product-service

logs-rabbitmq: ## Show logs for RabbitMQ
	docker-compose logs -f rabbitmq

status: ## Show container status
	@echo "$(BLUE)Container Status:$(NC)"
	@docker-compose ps

##@ Database Management

db-create: ## Create databases for both services
	@echo "$(BLUE)Creating databases...$(NC)"
	@echo "$(YELLOW)📦 Product Service Database:$(NC)"
	@docker-compose exec product-service php bin/console doctrine:migrations:migrate --no-interaction || echo "$(YELLOW)⚠️ Product database migration failed$(NC)"
	@echo "$(YELLOW)📦 Order Service Database:$(NC)"
	@docker-compose exec order-service php bin/console doctrine:migrations:migrate --no-interaction || echo "$(YELLOW)⚠️ Order database migration failed$(NC)"
	@echo "$(GREEN)✅ Databases created/updated$(NC)"

db-drop: ## Drop databases for both services
	@echo "$(BLUE)Dropping databases...$(NC)"
	@docker-compose exec product-service php bin/console doctrine:schema:drop --force || echo "$(YELLOW)⚠️ Product database schema doesn't exist$(NC)"
	@docker-compose exec order-service php bin/console doctrine:schema:drop --force || echo "$(YELLOW)⚠️ Order database schema doesn't exist$(NC)"
	@echo "$(GREEN)✅ Databases dropped$(NC)"

db-reset: db-drop db-create ## Reset databases (drop and recreate)

##@ Testing

test: test-unit test-integration ## Run all tests (unit + integration)

test-unit: ## Run unit tests for all services
	@echo "$(BLUE)Running unit tests...$(NC)"
	@echo "$(YELLOW)📦 Order-Bundle Tests:$(NC)"
	@docker-compose exec order-service bash -c "cd order-bundle && vendor/bin/phpunit --no-coverage --testdox" || (echo "$(RED)❌ Order-Bundle tests failed$(NC)" && exit 1)
	@echo ""
	@echo "$(YELLOW)📦 Product-Service Tests:$(NC)"
	@docker-compose exec product-service ./bin/phpunit --configuration phpunit.unit.xml --testdox || (echo "$(RED)❌ Product-Service tests failed$(NC)" && exit 1)
	@echo ""
	@echo "$(YELLOW)📦 Order-Service Tests:$(NC)"
	@docker-compose exec order-service ./bin/phpunit --configuration phpunit.unit.xml --testdox || (echo "$(RED)❌ Order-Service tests failed$(NC)" && exit 1)
	@echo ""
	@echo "$(GREEN)✅ All unit tests passed!$(NC)"

test-order-bundle: ## Run order-bundle unit tests only
	@echo "$(BLUE)Running Order-Bundle unit tests...$(NC)"
	docker-compose exec order-service bash -c "cd order-bundle && vendor/bin/phpunit --testdox"

test-product-service: ## Run product-service unit tests only
	@echo "$(BLUE)Running Product-Service unit tests...$(NC)"
	docker-compose exec product-service ./bin/phpunit --configuration phpunit.unit.xml --testdox

test-order-service: ## Run order-service unit tests only
	@echo "$(BLUE)Running Order-Service unit tests...$(NC)"
	docker-compose exec order-service ./bin/phpunit --configuration phpunit.unit.xml --testdox

test-integration: ## Run integration tests
	@echo "$(BLUE)Running integration tests...$(NC)"
	@./run-integration-test.sh

test-coverage: ## Generate test coverage reports
	@echo "$(BLUE)Generating test coverage reports...$(NC)"
	@echo "$(YELLOW)📊 Order-Bundle Coverage:$(NC)"
	@docker-compose exec order-service bash -c "cd order-bundle && vendor/bin/phpunit --coverage-html coverage/"
	@echo "$(YELLOW)📊 Product-Service Coverage:$(NC)"
	@docker-compose exec product-service ./bin/phpunit --configuration phpunit.unit.xml --coverage-html coverage/
	@echo "$(YELLOW)📊 Order-Service Coverage:$(NC)"
	@docker-compose exec order-service ./bin/phpunit --configuration phpunit.unit.xml --coverage-html coverage/
	@echo "$(GREEN)✅ Coverage reports generated in each service's coverage/ directory$(NC)"

##@ Development

install: ## Install dependencies for all services
	@echo "$(BLUE)Installing dependencies...$(NC)"
	@echo "$(YELLOW)📦 Order-Bundle:$(NC)"
	@docker-compose exec order-service bash -c "cd order-bundle && composer install"
	@echo "$(YELLOW)📦 Product-Service:$(NC)"
	@docker-compose exec product-service composer install
	@echo "$(YELLOW)📦 Order-Service:$(NC)"
	@docker-compose exec order-service composer install
	@echo "$(GREEN)✅ All dependencies installed$(NC)"

setup: start install db-create ## Complete project setup (start, install, create DBs)
	@echo "$(GREEN)🎉 Project setup complete!$(NC)"
	@$(MAKE) --no-print-directory health

clean: ## Clean up containers, volumes, and networks
	@echo "$(BLUE)Cleaning up...$(NC)"
	docker-compose down -v --remove-orphans
	docker system prune -f
	@echo "$(GREEN)✅ Cleanup complete$(NC)"

##@ Messaging

consumer-start: ## Start RabbitMQ consumer for product events
	@echo "$(BLUE)Starting RabbitMQ consumer...$(NC)"
	docker-compose exec product-service php bin/console messenger:consume order_events -vv

consumer-stop: ## Stop RabbitMQ consumer
	@echo "$(BLUE)Stopping RabbitMQ consumer...$(NC)"
	docker-compose exec product-service pkill -f "messenger:consume" || true

rabbitmq-ui: ## Open RabbitMQ management UI
	@echo "$(BLUE)RabbitMQ Management UI: http://localhost:15672$(NC)"
	@echo "$(YELLOW)Username: admin, Password: pass$(NC)"

##@ Health & Monitoring

health: ## Check health of all services
	@echo "$(BLUE)Checking service health...$(NC)"
	@echo "$(YELLOW)🔍 Product Service:$(NC)"
	@docker-compose exec product-service curl -s http://product-service/health | grep -q "ok" && echo "$(GREEN)✅ Product Service: Healthy$(NC)" || echo "$(RED)❌ Product Service: Unhealthy$(NC)"
	@echo "$(YELLOW)🔍 Order Service:$(NC)"
	@docker-compose exec order-service curl -s http://order-service/health | grep -q "ok" && echo "$(GREEN)✅ Order Service: Healthy$(NC)" || echo "$(RED)❌ Order Service: Unhealthy$(NC)"
	@echo "$(YELLOW)🔍 RabbitMQ:$(NC)"
	@curl -s -u admin:pass http://localhost:15672/api/overview > /dev/null && echo "$(GREEN)✅ RabbitMQ: Healthy$(NC)" || echo "$(RED)❌ RabbitMQ: Unhealthy$(NC)"

wait-for-services: ## Wait for all services to be ready
	@echo "$(BLUE)Waiting for services to be ready...$(NC)"
	@timeout=60; \
	while [ $$timeout -gt 0 ]; do \
		if docker-compose exec -T product-service curl -s http://product-service/health > /dev/null 2>&1 && \
		   docker-compose exec -T order-service curl -s http://order-service/health > /dev/null 2>&1; then \
			echo "$(GREEN)✅ All services are ready$(NC)"; \
			break; \
		fi; \
		echo "$(YELLOW)⏳ Waiting for services... ($$timeout seconds remaining)$(NC)"; \
		sleep 2; \
		timeout=$$((timeout-2)); \
	done; \
	if [ $$timeout -le 0 ]; then \
		echo "$(RED)❌ Services failed to start within 60 seconds$(NC)"; \
		exit 1; \
	fi

##@ Quick Actions

demo: setup test ## Complete demo: setup + run all tests
	@echo "$(GREEN)🎉 Demo complete! All systems working.$(NC)"

ci: ## Continuous Integration pipeline
	@echo "$(BLUE)Running CI pipeline...$(NC)"
	@$(MAKE) --no-print-directory build
	@$(MAKE) --no-print-directory start
	@$(MAKE) --no-print-directory install
	@$(MAKE) --no-print-directory db-create
	@$(MAKE) --no-print-directory test
	@echo "$(GREEN)✅ CI pipeline completed successfully$(NC)"

dev: start install db-create health ## Quick development setup

##@ Information

urls: ## Show all service URLs
	@echo "$(BLUE)Service URLs:$(NC)"
	@echo "$(YELLOW)🌐 Product Service:$(NC) http://localhost:8080"
	@echo "$(YELLOW)🌐 Order Service:$(NC) http://localhost:8081"
	@echo "$(YELLOW)🐰 RabbitMQ Management:$(NC) http://localhost:15672 (admin/pass)"
	@echo "$(YELLOW)🐘 PostgreSQL Product DB:$(NC) localhost:5432 (app/!ChangeMe!)"
	@echo "$(YELLOW)🐘 PostgreSQL Order DB:$(NC) localhost:5433 (app/!ChangeMe!)"

help: ## Display this help message
	@echo "$(BLUE)Symfony Docker Project - Event-Driven Microservices$(NC)"
	@echo ""
	@echo "$(YELLOW)Available commands:$(NC)"
	@awk 'BEGIN {FS = ":.*##"; printf ""} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(BLUE)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
	@echo ""
	@echo "$(YELLOW)Quick Start:$(NC)"
	@echo "  make setup    # Complete project setup"
	@echo "  make test     # Run all tests"
	@echo "  make demo     # Full demo (setup + tests)"
	@echo ""
	@echo "$(YELLOW)Examples:$(NC)"
	@echo "  make start && make test-integration"
	@echo "  make dev && make consumer-start"
	@echo "  make ci  # Full CI pipeline"
