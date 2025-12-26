<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

/**
 * Base exception for all schema reference resolution failures.
 *
 * This abstract exception is raised when a $ref keyword in a JSON schema cannot
 * be successfully resolved to its target schema definition. Reference resolution
 * can fail for multiple reasons: the reference path may be malformed (invalid
 * JSON Pointer syntax), the referenced schema location may not exist within the
 * document, or an external schema resource may be unreachable or missing.
 *
 * Subclasses provide specific error contexts for different types of resolution
 * failures, such as invalid JSON Pointer syntax or failed external resource loading.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3.1 Reference Resolution Rules
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-9.4.1 URI References
 * @see https://json-schema.org/understanding-json-schema/structuring#ref Understanding $ref Keyword
 * @see https://json-schema.org/understanding-json-schema/structuring#defs Schema Definitions and References
 * @see https://datatracker.ietf.org/doc/html/rfc6901 JSON Pointer RFC 6901
 * @see https://datatracker.ietf.org/doc/html/rfc3986 URI Generic Syntax RFC 3986
 */
abstract class UnresolvedReferenceException extends JsonSchemaException
{
    // Abstract base - no factory methods
}
