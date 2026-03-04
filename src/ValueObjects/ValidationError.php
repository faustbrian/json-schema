<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\ValueObjects;

/**
 * Represents a single validation error from JSON Schema validation.
 *
 * Immutable value object that encapsulates a single validation failure including
 * its location within the data structure, descriptive error message, and the
 * specific JSON Schema keyword that triggered the validation failure. This object
 * provides structured error information for debugging and user feedback.
 *
 * Each error captures three essential pieces of information:
 * - Path: The location in the data where validation failed (e.g., "/user/email")
 * - Message: Human-readable description of what went wrong
 * - Keyword: The schema keyword that failed (e.g., "type", "required", "pattern")
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference JSON Schema Reference
 * @see https://json-schema.org/draft/2020-12/json-schema-core#name-output-schemas JSON Schema Output Format
 * @see https://json-schema.org/understanding-json-schema/reference/generic JSON Schema Validation Keywords
 * @see ValidationResult Container for multiple validation errors
 */
final readonly class ValidationError
{
    /**
     * Create a new validation error.
     *
     * @param string $path    The JSON path where the error occurred, using dot notation or
     *                        JSON Pointer format to indicate the exact location in the data
     *                        structure that failed validation (e.g., "/properties/email")
     * @param string $message Human-readable error message describing the validation failure,
     *                        suitable for display to end users or logging for debugging
     * @param string $keyword The JSON Schema keyword that caused the validation to fail
     *                        (e.g., "type", "minLength", "pattern", "required", "enum")
     */
    public function __construct(
        public string $path,
        public string $message,
        public string $keyword,
    ) {}

    /**
     * Get the keyword that failed validation.
     *
     * @return string The schema keyword that caused the validation failure
     */
    public function keyword(): string
    {
        return $this->keyword;
    }

    /**
     * Get the error message.
     *
     * @return string The human-readable error message describing the failure
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Get the JSON path where the error occurred.
     *
     * @return string The path to the location in the data structure that failed validation
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Convert error to array representation.
     *
     * Serializes the error to an associative array containing all error details.
     * Useful for JSON encoding, logging, or API responses containing validation
     * error information.
     *
     * @return array{path: string, message: string, keyword: string} The error as an array
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'message' => $this->message,
            'keyword' => $this->keyword,
        ];
    }
}
