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
