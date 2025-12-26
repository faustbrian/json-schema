<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

/**
 * Base exception for all invalid schema definition errors.
 *
 * This abstract exception serves as the parent for all exceptions related to
 * problems with the schema definition itself, rather than validation failures
 * of data against a valid schema. Subclasses indicate specific types of schema
 * invalidity such as malformed JSON, missing required keywords, or structural
 * violations of the JSON Schema specification.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Specification
 * @see https://json-schema.org/understanding-json-schema/structuring Understanding Schema Structuring
 * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Keywords and Structure
 */
abstract class InvalidSchemaException extends JsonSchemaException {}
