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
 * Exception thrown when a JSON pointer is invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidJsonPointerException extends UnresolvedReferenceException
{
    /**
     * Create exception for invalid JSON pointer.
     *
     * @param string $pointer The invalid JSON pointer
     */
    public static function forPointer(string $pointer): self
    {
        return new self(sprintf('Invalid JSON pointer: %s', $pointer));
    }
}
