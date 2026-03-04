<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Factories\ValidatorFactory;
use Cline\JsonSchema\Generators\SchemaGenerator;
use Cline\JsonSchema\Support\LazyValidator;
use Cline\JsonSchema\Support\SchemaCompiler;
use Cline\JsonSchema\Support\SchemaDiffer;
use Cline\JsonSchema\Support\SchemaMerger;
use Cline\JsonSchema\Support\SchemaMigrator;
use Cline\JsonSchema\Support\SchemaValidator;
use Cline\JsonSchema\ValueObjects\Schema;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function is_string;

/**
 * Central manager for JSON Schema validation operations.
 *
 * This class serves as the main entry point for validating data against JSON schemas.
 * It coordinates draft version detection, validator factory instantiation, and the
 * validation process itself. The manager automatically detects the JSON Schema draft
 * version from the schema's $schema keyword, or allows explicit draft specification
 * for schemas without version declarations.
 *
 * The manager is immutable and thread-safe, delegating stateless validation operations
 * to draft-specific validator implementations created by the ValidatorFactory.
 *
 * ```php
 * $manager = new JsonSchemaManager(new ValidatorFactory());
 *
 * $result = $manager->validate(
 *     ['name' => 'John', 'age' => 30],
 *     [
 *         'type' => 'object',
 *         'properties' => [
 *             'name' => ['type' => 'string'],
 *             'age' => ['type' => 'integer', 'minimum' => 0],
 *         ],
 *         'required' => ['name'],
 *     ]
 * );
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Specification
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema Guide
 * @see https://json-schema.org/understanding-json-schema/basics Validation Basics
 * @see https://json-schema.org/specification JSON Schema Specification Overview
 */
final readonly class JsonSchemaManager
{
    /**
     * Create a new JSON Schema validation manager.
     *
     * @param ValidatorFactory $factory The factory used to create draft-specific validators
     *                                  for processing schemas of different JSON Schema versions
     */
    public function __construct(
        private ValidatorFactory $factory,
    ) {}

    /**
     * Validate data against a JSON schema definition.
     *
     * Performs comprehensive validation of the provided data against the JSON schema,
     * automatically detecting the schema draft version or using the explicitly provided
     * draft parameter. Returns a ValidationResult containing validation status and any
     * errors encountered during validation.
     *
     * Draft version detection examines the $schema keyword in the schema definition.
     * If no $schema is present or the draft parameter is provided, the specified or
     * default draft (2020-12) is used for validation.
     *
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-12 Validation Output
     * @see https://json-schema.org/understanding-json-schema/basics Understanding Validation Basics
     * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Structure
     * @param  mixed                $data   The data to validate - can be any JSON-compatible type
     *                                      including objects, arrays, strings, numbers, booleans, or null
     * @param  array<string, mixed> $schema The JSON schema definition as an associative array with
     *                                      schema keywords like type, properties, required, etc.
     * @param  null|Draft           $draft  Optional explicit draft version to use for validation.
     *                                      When null, the draft is auto-detected from the $schema keyword
     *                                      or defaults to Draft 2020-12
     * @return ValidationResult     The validation result containing success status, error messages,
     *                              and detailed validation context including JSON paths for failures
     */
    public function validate(mixed $data, array $schema, ?Draft $draft = null): ValidationResult
    {
        $draft ??= $this->detectDraft($schema);
        $validator = $this->factory->create($draft);

        return $validator->validate($data, $schema);
    }

    /**
     * Generate a JSON Schema from data sample.
     *
     * Analyzes the provided data and generates a schema that would validate
     * that data. Supports primitives, objects, arrays, and nested structures.
     *
     * @param mixed $data  The data to generate a schema from
     * @param Draft $draft The JSON Schema draft version to generate
     *
     * @return Schema Generated schema that validates the input data
     */
    public function generate(mixed $data, Draft $draft = Draft::Draft202012): Schema
    {
        return SchemaGenerator::generate($data, $draft);
    }

    /**
     * Generate a JSON Schema from multiple data samples.
     *
     * Analyzes multiple data samples and generates a schema that would
     * validate all of them. Useful for creating schemas from a dataset.
     *
     * @param array<mixed> $samples Array of data samples to analyze
     * @param Draft        $draft   The JSON Schema draft version to generate
     *
     * @return Schema Generated schema that validates all samples
     */
    public function generateFromSamples(array $samples, Draft $draft = Draft::Draft202012): Schema
    {
        return SchemaGenerator::generateFromSamples($samples, $draft);
    }

    /**
     * Validate a schema against its meta-schema.
     *
     * Ensures that the schema definition is well-formed and complies
     * with the JSON Schema specification.
     *
     * @param array<string, mixed> $schema The schema to validate
     * @param null|Draft           $draft  The draft version to validate against
     *
     * @return ValidationResult Validation result indicating if schema is valid
     */
    public function validateSchema(array $schema, ?Draft $draft = null): ValidationResult
    {
        return SchemaValidator::validateSchema($schema, $draft, $this->factory);
    }

    /**
     * Check if a schema is valid without detailed errors.
     *
     * Convenience method for quick schema validation.
     *
     * @param array<string, mixed> $schema The schema to validate
     * @param null|Draft           $draft  The draft version to validate against
     *
     * @return bool True if the schema is valid, false otherwise
     */
    public function isValidSchema(array $schema, ?Draft $draft = null): bool
    {
        return SchemaValidator::isValidSchema($schema, $draft, $this->factory);
    }

    /**
     * Validate data with lazy evaluation (stop on first error).
     *
     * Performs fail-fast validation that returns immediately upon
     * encountering the first error. More efficient when only validity
     * status is needed.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema to validate against
     * @param null|Draft           $draft  The draft version to use
     *
     * @return ValidationResult Result with at most one error
     */
    public function validateLazy(mixed $data, array $schema, ?Draft $draft = null): ValidationResult
    {
        $draft ??= $this->detectDraft($schema);
        $validator = $this->factory->create($draft);

        return LazyValidator::validate($validator, $data, $schema);
    }

    /**
     * Compile a schema for improved performance.
     *
     * Pre-processes the schema and caches the result for faster
     * subsequent validations.
     *
     * @param array<string, mixed> $schema The schema to compile
     *
     * @return array<string, mixed> Compiled schema
     */
    public function compileSchema(array $schema): array
    {
        return SchemaCompiler::compile($schema);
    }

    /**
     * Clear the schema compilation cache.
     */
    public function clearCompilationCache(): void
    {
        SchemaCompiler::clearCache();
    }

    /**
     * Merge schemas using allOf composition.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public function mergeAllOf(array $schemas): array
    {
        return SchemaMerger::mergeAllOf($schemas);
    }

    /**
     * Merge schemas using anyOf composition.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public function mergeAnyOf(array $schemas): array
    {
        return SchemaMerger::mergeAnyOf($schemas);
    }

    /**
     * Merge schemas using oneOf composition.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public function mergeOneOf(array $schemas): array
    {
        return SchemaMerger::mergeOneOf($schemas);
    }

    /**
     * Deep merge two schemas.
     *
     * @param array<string, mixed> $schema1 First schema
     * @param array<string, mixed> $schema2 Second schema
     *
     * @return array<string, mixed> Merged schema
     */
    public function deepMerge(array $schema1, array $schema2): array
    {
        return SchemaMerger::deepMerge($schema1, $schema2);
    }

    /**
     * Compare two schemas and return differences.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return array{added: array<string, mixed>, removed: array<string, mixed>, changed: array<string, array{old: mixed, new: mixed}>} Schema differences
     */
    public function diffSchemas(array $oldSchema, array $newSchema): array
    {
        return SchemaDiffer::diff($oldSchema, $newSchema);
    }

    /**
     * Check if schema changes are breaking.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return bool True if changes are breaking
     */
    public function hasBreakingChanges(array $oldSchema, array $newSchema): bool
    {
        return SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);
    }

    /**
     * Migrate schema to a different draft version.
     *
     * @param array<string, mixed> $schema      The schema to migrate
     * @param Draft                $targetDraft The target draft version
     *
     * @return array<string, mixed> Migrated schema
     */
    public function migrateSchema(array $schema, Draft $targetDraft): array
    {
        return SchemaMigrator::migrate($schema, $targetDraft);
    }

    /**
     * Detect the JSON Schema draft version from the schema's $schema keyword.
     *
     * Examines the schema definition for a $schema keyword containing a URI that
     * identifies the JSON Schema specification version. The URI is parsed to determine
     * which draft validator should process the schema. If no $schema keyword is present
     * or the URI is unrecognized, defaults to the latest supported draft (2020-12).
     *
     * @param array<string, mixed> $schema The schema definition to inspect for draft version metadata
     *
     * @return Draft The detected draft version enum value, or Draft202012 as the default fallback
     *               when no $schema keyword is present or the version cannot be determined
     */
    private function detectDraft(array $schema): Draft
    {
        if (isset($schema['$schema']) && is_string($schema['$schema'])) {
            return Draft::fromSchemaUri($schema['$schema']) ?? Draft::Draft202012;
        }

        return Draft::Draft202012;
    }
}
