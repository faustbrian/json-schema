<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

use function sprintf;

/**
 * Exception thrown when a JSON schema definition cannot be parsed or is malformed.
 *
 * This exception is raised when the schema structure itself is invalid, such as
 * when JSON syntax errors are encountered, required schema structure is malformed,
 * or the schema violates basic JSON Schema construction rules. This is distinct
 * from schema validation failures - this indicates the schema definition itself
 * is broken, not that data failed to validate against it.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Specification
 * @see https://json-schema.org/understanding-json-schema/basics Understanding JSON Schema Basics
 * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Structure and Syntax
 */
final class InvalidJsonSchemaException extends InvalidSchemaException
{
    /**
     * Create an exception for an invalid JSON schema with a specific reason.
     *
     * @param string $reason The specific reason why the JSON schema is invalid,
     *                       such as "malformed JSON syntax" or "invalid $schema value"
     *
     * @return self The configured exception instance
     */
    public static function fromReason(string $reason): self
    {
        return new self(sprintf('Invalid JSON schema: %s', $reason));
    }
}
