<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Exceptions;

use Cline\JsonSchema\Enums\Draft;

use function sprintf;

/**
 * Exception thrown when an unsupported JSON Schema draft is encountered.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DraftNotSupportedException extends UnsupportedDraftException
{
    /**
     * Create exception for unsupported draft.
     *
     * @param Draft $draft The unsupported draft version
     */
    public static function forDraft(Draft $draft): self
    {
        return new self(sprintf('Unsupported JSON Schema draft: %s', $draft->label()));
    }
}
