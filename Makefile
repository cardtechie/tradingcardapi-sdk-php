.PHONY: help test test-coverage analyse format install up down shell status build ensure-running

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Docker container management
build: ## Build the Docker containers
	docker compose build

up: ## Start the Docker containers
	docker compose up -d

down: ## Stop the Docker containers
	docker compose down

status: ## Show container status
	docker compose ps

shell: ## Access the container shell
	@make ensure-running
	docker compose exec dev bash

# Helper target to ensure container is running
ensure-running:
	@if ! docker compose ps --services --filter "status=running" | grep -q "dev"; then \
		echo "Starting dev container..."; \
		docker compose up -d dev; \
		echo "Waiting for container to be ready..."; \
		sleep 3; \
	fi

# Package management
install: ## Install composer dependencies
	@make ensure-running
	docker compose exec dev composer install

# Testing
test: ## Run all tests using Pest
	@make ensure-running
	docker compose exec dev composer test

test-coverage: ## Run tests with coverage report
	@make ensure-running
	docker compose exec dev composer test-coverage

pest: ## Run Pest directly
	@make ensure-running
	docker compose exec dev vendor/bin/pest

# Code quality
analyse: ## Run PHPStan static analysis
	@make ensure-running
	docker compose exec dev composer analyse

format: ## Format code using Laravel Pint
	@make ensure-running
	docker compose exec dev composer format

phpstan: ## Run PHPStan directly
	@make ensure-running
	docker compose exec dev vendor/bin/phpstan analyse

pint: ## Run Laravel Pint directly
	@make ensure-running
	docker compose exec dev vendor/bin/pint

# Quality assurance tasks
check: ## Run all quality checks (tests + analysis + format check)
	@make test
	@make analyse
	@make format-check

quality: ## Run comprehensive quality checks with coverage
	@make test-coverage
	@make analyse
	@make format-check

format-check: ## Check if code formatting is correct (dry-run)
	@make ensure-running
	docker compose exec dev vendor/bin/pint --test

# Combined tasks
ci: test analyse format-check ## Run continuous integration tasks (tests + analysis + format check)

fix: format analyse ## Format code and run analysis

all: install test analyse format ## Install dependencies, run tests, analysis, and format code