<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

use RuntimeException;

/**
 * Root exception for all JSON Schema validation and processing errors.
 *
 * This abstract exception serves as the base for all exceptions thrown by
 * the JSON Schema package. All schema-related errors extend from this class,
 * allowing consumers to catch all package exceptions with a single catch block.
 * Specific exception types are provided through subclasses for granular error
 * handling of schema validation, reference resolution, draft detection, and
 * schema construction failures.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/specification JSON Schema Specification Overview
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Specification
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema Guide
 */
abstract class JsonSchemaException extends RuntimeException {}
