<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

/**
 * Exception thrown when no draft can be detected from schema.
 *
 * Indicates that a JSON Schema document does not specify a recognizable
 * draft version via the $schema keyword, or the specified version is not
 * supported by the validator. This prevents the validator from determining
 * which validation rules to apply.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/schema#schema
 * @see https://json-schema.org/specification
 */
final class DraftCannotBeDetectedException extends UnsupportedDraftException
{
    /**
     * Create exception when no draft can be detected.
     *
     * Factory method to construct an exception indicating that the schema's
     * draft version could not be determined or is not recognized.
     *
     * @return self The exception instance with standard error message
     */
    public static function fromSchema(): self
    {
        return new self('Unable to detect JSON Schema draft version from schema');
    }
}
