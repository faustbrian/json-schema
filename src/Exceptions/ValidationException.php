<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

use Cline\JsonSchema\ValueObjects\ValidationResult;

use function implode;
use function sprintf;

/**
 * Exception thrown when data validation against a JSON schema fails.
 *
 * This exception wraps a ValidationResult object to provide detailed information
 * about validation failures, including all error messages and their JSON paths.
 * While validation failures are often handled through normal flow control by
 * checking the ValidationResult directly, this exception is useful when validation
 * errors should halt execution and be treated as exceptional conditions, such as
 * in strict validation contexts or when processing critical configuration data.
 *
 * The exception message includes a formatted summary of all validation errors with
 * their paths, making it suitable for logging and error reporting.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-12 Validation Output Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Keywords
 * @see https://json-schema.org/understanding-json-schema/reference/generic Understanding Validation Process
 * @see https://json-schema.org/understanding-json-schema/basics Basic Validation Concepts
 */
final class ValidationException extends JsonSchemaException
{
    /**
     * Create a new validation exception from a validation result.
     *
     * Constructs an exception with a formatted message listing all validation errors
     * and their JSON paths. The ValidationResult is stored for programmatic access
     * to detailed error information.
     *
     * @param ValidationResult $result The validation result containing one or more validation errors,
     *                                 including error messages, JSON paths, and schema contexts
     */
    public function __construct(
        public readonly ValidationResult $result,
    ) {
        $errors = [];

        foreach ($result->getErrors() as $error) {
            $errors[] = sprintf('[%s] %s', $error->path, $error->message);
        }

        parent::__construct(sprintf('Validation failed: %s', implode(', ', $errors)));
    }

    /**
     * Retrieve the complete validation result with all error details.
     *
     * Provides access to the underlying ValidationResult object, which contains
     * the full set of validation errors with their paths, messages, and contexts.
     * This allows for programmatic error inspection and custom error handling logic.
     *
     * @return ValidationResult The validation result containing all validation errors and their metadata
     */
    public function getResult(): ValidationResult
    {
        return $this->result;
    }
}
