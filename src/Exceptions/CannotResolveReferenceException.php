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
 * Exception thrown when a schema reference cannot be resolved.
 *
 * Indicates that a $ref pointer in a JSON Schema document could not be
 * resolved to a valid schema fragment. This occurs when the reference
 * points to a non-existent location or an external resource that cannot
 * be loaded.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/structuring#dollarref
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3
 */
final class CannotResolveReferenceException extends UnresolvedReferenceException
{
    /**
     * Create exception for unresolved reference.
     *
     * Factory method to construct an exception with a formatted message
     * indicating which reference failed to resolve.
     *
     * @param string $reference The JSON Pointer or URI reference that could not be resolved
     *
     * @return self The exception instance with formatted error message
     */
    public static function forReference(string $reference): self
    {
        return new self(sprintf('Unable to resolve reference: %s', $reference));
    }
}
