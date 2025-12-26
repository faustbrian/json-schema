.PHONY: help build shell destroy composer lint refactor test test\:lint test\:refactor test\:type-coverage test\:types test\:unit sync-compliance sync-test-suite sync-meta-schemas test-compliance compliance clean-compliance

compose_command = docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php85

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker containers
	docker-compose build

shell: build ## Open shell in PHP container
	$(compose_command) bash

destroy: ## Destroy Docker containers and volumes
	docker-compose down -v

composer: build ## Install Composer dependencies
	$(compose_command) composer install

lint: build ## Run code style linter with fixes
	$(compose_command) composer lint

refactor: build ## Run Rector refactoring
	$(compose_command) composer refactor

test: build ## Run all tests
	$(compose_command) composer test

test\:lint: build ## Run code style linter (check only)
	$(compose_command) composer test:lint

test\:refactor: build ## Run Rector (check only)
	$(compose_command) composer test:refactor

test\:type-coverage: build ## Run type coverage tests
	$(compose_command) composer test:type-coverage

test\:types: build ## Run PHPStan type checking
	$(compose_command) composer test:types

test\:unit: build ## Run unit tests
	$(compose_command) composer test:unit

sync-compliance: sync-test-suite sync-meta-schemas ## Sync all compliance test resources

sync-test-suite: ## Clone/update JSON-Schema-Test-Suite
	@echo "Syncing JSON-Schema-Test-Suite..."
	@if [ -e "compliance/JSON-Schema-Test-Suite/.git" ]; then \
		echo "  Updating existing repository..."; \
		cd compliance/JSON-Schema-Test-Suite && git pull; \
	else \
		echo "  Cloning repository..."; \
		mkdir -p compliance; \
		git clone https://github.com/json-schema-org/JSON-Schema-Test-Suite.git compliance/JSON-Schema-Test-Suite; \
	fi
	@echo "✓ JSON-Schema-Test-Suite synced"

sync-meta-schemas: ## Download meta-schema vocabulary files for all drafts
	@echo "Syncing meta-schemas..."
	@bash compliance/sync-meta-schemas.sh 2019-09
	@bash compliance/sync-meta-schemas.sh 2020-12
	@echo ""
	@echo "Downloading main metaschemas..."
	@mkdir -p compliance/JSON-Schema-Test-Suite/remotes/draft2019-09
	@curl -sSL https://json-schema.org/draft/2019-09/schema -o compliance/JSON-Schema-Test-Suite/remotes/draft2019-09/schema
	@mkdir -p compliance/JSON-Schema-Test-Suite/remotes/draft2020-12
	@curl -sSL https://json-schema.org/draft/2020-12/schema -o compliance/JSON-Schema-Test-Suite/remotes/draft2020-12/schema
	@echo "✓ All meta-schemas synced"

compliance: ## Run compliance CLI tool
	./vendor/bin/prism

clean-compliance: ## Remove compliance test suite (can be re-synced)
	@echo "Cleaning compliance directory..."
	@rm -rf compliance/JSON-Schema-Test-Suite
	@echo "✓ Compliance directory cleaned (run 'make sync-compliance' to restore)"
