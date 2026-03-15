set shell := ["bash", "-uc"]

compose_command := `echo docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php85`

help:
    just --list

build:
    docker-compose build

shell: build
    {{ compose_command }} bash

destroy:
    docker-compose down -v

composer: build
    {{ compose_command }} composer install

lint: build
    {{ compose_command }} composer lint

refactor: build
    {{ compose_command }} composer refactor

test: build
    {{ compose_command }} composer test

test-lint: build
    {{ compose_command }} composer test:lint

test-refactor: build
    {{ compose_command }} composer test:refactor

test-type-coverage: build
    {{ compose_command }} composer test:type-coverage

test-types: build
    {{ compose_command }} composer test:types

test-unit: build
    {{ compose_command }} composer test:unit

sync-compliance: sync-test-suite sync-meta-schemas

sync-test-suite:
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

sync-meta-schemas:
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

compliance:
    ./vendor/bin/prism

clean-compliance:
    @echo "Cleaning compliance directory..."
    @rm -rf compliance/JSON-Schema-Test-Suite
    @echo "✓ Compliance directory cleaned (run 'just sync-compliance' to restore)"
