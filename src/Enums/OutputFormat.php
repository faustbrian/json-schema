<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Enums;

/**
 * JSON Schema validation output formats.
 *
 * Defines the standard output format levels specified in JSON Schema 2020-12
 * for presenting validation results. Each format provides a different level
 * of detail about validation failures and annotations.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/draft-bhutton-json-schema-00#section-12.4 Output Structure
 * @see https://json-schema.org/blog/posts/interpreting-output Interpreting JSON Schema Output
 */
enum OutputFormat: string
{
    /**
     * Flag format - minimal boolean result.
     *
     * Returns only a boolean indicating validation success or failure.
     * Provides no error details or annotations.
     *
     * Example: {"valid": false}
     */
    case Flag = 'flag';

    /**
     * Basic format - flat error list.
     *
     * Returns validation result with a flat list of all errors.
     * Includes instance location, keyword location, and error messages.
     *
     * Example: {"valid": false, "errors": [{...}, {...}]}
     */
    case Basic = 'basic';

    /**
     * Detailed format - hierarchical error structure.
     *
     * Returns validation result with nested error structures that
     * follow the schema validation hierarchy. Includes locations
     * and selective error details.
     */
    case Detailed = 'detailed';

    /**
     * Verbose format - complete evaluation details.
     *
     * Returns the most comprehensive output including all evaluation
     * paths, absolute schema locations, and complete error hierarchy.
     * Useful for debugging complex validation failures.
     */
    case Verbose = 'verbose';
}
