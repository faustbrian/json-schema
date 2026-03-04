<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\ValueObjects;

use Cline\JsonSchema\Enums\OutputFormat;
use Cline\JsonSchema\Support\OutputFormatter;

use function array_map;

/**
 * Represents the result of JSON Schema validation.
 *
 * Immutable value object that encapsulates the complete outcome of validating data
 * against a JSON Schema. Contains a boolean validity flag and a collection of
 * detailed error objects for any validation failures. Provides convenience methods
 * for checking validation status and accessing error information.
 *
 * The result object follows a predictable pattern:
 * - Valid result: valid=true, errors=[]
 * - Invalid result: valid=false, errors=[ValidationError, ...]
 *
 * Factory methods provide semantic construction:
 * - ValidationResult::success() for valid data
 * - ValidationResult::failure($errors) for invalid data
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema
 * @see https://json-schema.org/draft/2020-12/json-schema-core#name-output-schemas JSON Schema Validation Output Format
 * @see https://json-schema.org/understanding-json-schema/reference/combining JSON Schema Combining Schemas
 * @see ValidationError Individual error details
 */
final readonly class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param bool                   $valid  Whether the data passed validation against the schema.
     *                                       True indicates all constraints were satisfied, false
     *                                       indicates one or more validation failures occurred
     * @param array<ValidationError> $errors Collection of validation errors encountered during validation.
     *                                       Empty array when valid is true, one or more error objects
     *                                       when valid is false, each describing a specific failure
     */
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {}

    /**
     * Create a successful validation result.
     *
     * Factory method for creating a result indicating that data validation
     * passed successfully with no errors.
     *
     * @return self A valid result with no errors
     */
    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * Create a failed validation result.
     *
     * Factory method for creating a result indicating that data validation
     * failed with one or more errors. Each error describes a specific
     * constraint violation.
     *
     * @param array<ValidationError> $errors Collection of validation errors describing all failures
     *
     * @return self An invalid result containing the validation errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Check if validation passed.
     *
     * @return bool True if data is valid according to the schema, false otherwise
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Check if validation failed.
     *
     * @return bool True if data is invalid (has errors), false if data is valid
     */
    public function isInvalid(): bool
    {
        return !$this->valid;
    }

    /**
     * Get all validation errors.
     *
     * @return array<ValidationError> Collection of all validation errors that occurred
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all validation errors (alias for getErrors).
     *
     * Provides a shorter method name for accessing the error collection.
     * Functionally identical to getErrors().
     *
     * @return array<ValidationError> Collection of all validation errors that occurred
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Convert result to array representation.
     *
     * Serializes the validation result and all errors to a plain associative
     * array structure. Useful for JSON encoding, API responses, or logging
     * validation results in a standardized format.
     *
     * @return array{valid: bool, errors: array<array{path: string, message: string, keyword: string}>} The result as an array
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => array_map(static fn (ValidationError $error): array => $error->toArray(), $this->errors),
        ];
    }

    /**
     * Format result according to JSON Schema output specification.
     *
     * Converts the validation result into one of the four standard output formats
     * defined in JSON Schema 2020-12: Flag, Basic, Detailed, or Verbose.
     *
     * @see https://json-schema.org/draft/2020-12/draft-bhutton-json-schema-00#section-12.4 Output Structure
     * @param  OutputFormat                                             $format The desired output format level
     * @return array{valid: bool, errors?: array<array<string, mixed>>} Formatted validation result
     */
    public function format(OutputFormat $format): array
    {
        return OutputFormatter::format($this, $format);
    }
}
