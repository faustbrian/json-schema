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
 * Exception thrown when a JSON schema is missing a required keyword.
 *
 * This exception is raised when schema validation or processing requires a
 * specific keyword to be present but it is not found in the schema definition.
 * For example, certain validation rules may require the presence of "type",
 * "properties", or other structural keywords. Missing required keywords indicate
 * an incomplete or improperly constructed schema that cannot be processed.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8 Schema Vocabularies
 * @see https://json-schema.org/understanding-json-schema/reference/schema Understanding Schema Keywords
 * @see https://json-schema.org/understanding-json-schema/basics Schema Basics and Required Keywords
 */
final class MissingSchemaKeywordException extends InvalidSchemaException
{
    /**
     * Create an exception for a missing required schema keyword.
     *
     * @param string $keyword The name of the required keyword that is missing from the schema,
     *                        such as "type", "properties", "$schema", or any other required keyword
     *
     * @return self The configured exception instance with a descriptive message
     */
    public static function forKeyword(string $keyword): self
    {
        return new self(sprintf('Schema is missing required keyword: %s', $keyword));
    }
}
