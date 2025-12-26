<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

/**
 * Base exception for unsupported JSON Schema draft version errors.
 *
 * This abstract exception is raised when validation is attempted against a schema
 * using a draft version that is not supported by this library. JSON Schema has
 * evolved through multiple draft versions (Draft 4, 6, 7, 2019-09, 2020-12), each
 * introducing new features and keywords. When a schema specifies a $schema URI
 * for an unsupported or unrecognized draft, or when no appropriate validator can
 * be instantiated for the requested draft version, this exception is thrown.
 *
 * Subclasses provide specific contexts such as inability to detect the draft version
 * or explicit notification that a particular draft is not implemented.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/specification JSON Schema Specification Overview
 * @see https://json-schema.org/specification#published-drafts Published Draft Versions
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.1.1 Schema Identification
 * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Version and $schema Keyword
 * @see https://json-schema.org/draft-04/json-schema-core JSON Schema Draft 04
 * @see https://json-schema.org/draft-06/json-schema-core JSON Schema Draft 06
 * @see https://json-schema.org/draft-07/json-schema-core JSON Schema Draft 07
 */
abstract class UnsupportedDraftException extends JsonSchemaException
{
    // Abstract base - no factory methods
}
