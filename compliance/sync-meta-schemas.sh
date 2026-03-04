#!/bin/bash

# Sync meta-schema files from json-schema.org
# Usage: ./compliance/sync-meta-schemas.sh [draft-version]
# Example: ./compliance/sync-meta-schemas.sh 2019-09

set -e

DRAFT="${1:-2019-09}"
BASE_URL="https://json-schema.org/draft/${DRAFT}/meta"
TARGET_DIR="compliance/JSON-Schema-Test-Suite/remotes/draft${DRAFT//-/}/meta"

echo "Syncing meta-schemas for draft ${DRAFT}..."
mkdir -p "${TARGET_DIR}"

# Meta vocabulary schemas (common to all drafts)
META_SCHEMAS=(
    "core"
    "applicator"
    "validation"
    "meta-data"
    "content"
)

# Draft 2019-09 uses "format", 2020-12 uses "format-annotation" and "format-assertion"
if [ "$DRAFT" = "2019-09" ]; then
    META_SCHEMAS+=("format")
elif [ "$DRAFT" = "2020-12" ]; then
    META_SCHEMAS+=("format-annotation" "format-assertion" "unevaluated")
fi

for schema in "${META_SCHEMAS[@]}"; do
    echo "  Downloading ${schema}..."
    curl -sSL "${BASE_URL}/${schema}" -o "${TARGET_DIR}/${schema}"
done

echo "✓ Meta-schemas synced to ${TARGET_DIR}"
ls -lh "${TARGET_DIR}"
