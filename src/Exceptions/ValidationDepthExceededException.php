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
 * Exception thrown when validation depth exceeds maximum allowed recursion.
 *
 * This exception prevents infinite recursion in circular schema references by
 * enforcing a maximum validation depth of 1000 levels.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ValidationDepthExceededException extends JsonSchemaException
{
    public static function maximumDepthReached(int $depth): self
    {
        return new self(
            sprintf('Validation depth exceeded %d, possible infinite recursion', $depth),
        );
    }
}
