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

# Release management commands
version: ## Generate version number for current branch
	@bash build/version.sh

version-preview: ## Preview version for specific branch (use --branch=<name>)
	@bash build/version.sh $(filter-out $@,$(MAKECMDGOALS))

changelog-update: ## Update changelog for next version
	@echo "Updating changelog for version: $$(bash build/version.sh)"
	@bash build/update-changelog.sh "$$(bash build/version.sh)"

changelog-preview: ## Preview unreleased changes  
	@echo "Current version would be: $$(bash build/version.sh)"
	@echo ""
	@echo "Recent commits that would be included:"
	@git log --oneline -10

release-notes-preview: ## Generate release notes preview
	@echo "Generating release notes for version: $$(bash build/version.sh)"
	@bash build/generate-release-notes.sh "$$(bash build/version.sh)"

release-notes: ## Generate release notes (use VERSION=x.x.x)
	@if [ -z "$(VERSION)" ]; then \
		echo "ERROR: VERSION is required. Usage: make release-notes VERSION=1.0.0"; \
		exit 1; \
	fi
	@bash build/generate-release-notes.sh "$(VERSION)"

# Version bump helpers
version-bump-develop: ## Update changelog and version for develop branch
	@make ensure-running
	@VERSION=$$(bash build/version.sh --branch=develop) && \
	echo "Preparing version bump for develop branch: $$VERSION" && \
	bash build/update-changelog.sh "$$VERSION" && \
	echo "Updated changelog for version: $$VERSION"

version-bump-main: ## Update changelog and version for main branch  
	@make ensure-running
	@VERSION=$$(bash build/version.sh --branch=main) && \
	echo "Preparing version bump for main branch: $$VERSION" && \
	bash build/update-changelog.sh "$$VERSION" && \
	echo "Updated changelog for version: $$VERSION"

# Combined tasks
ci: test analyse format-check ## Run continuous integration tasks (tests + analysis + format check)

fix: format analyse ## Format code and run analysis

all: install test analyse format ## Install dependencies, run tests, analysis, and format code