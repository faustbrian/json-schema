<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use function array_all;
use function array_keys;
use function count;
use function is_array;
use function is_float;
use function is_int;
use function sort;

/**
 * JSON value comparison utilities for schema validation.
 *
 * Provides JSON Schema-compliant equality comparison that treats numeric values
 * by their mathematical value rather than PHP type. This is essential for the
 * const, enum, and uniqueItems keywords.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-6.1.2 const keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-6.1.3 enum keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-6.4.3 uniqueItems keyword
 * @see https://json-schema.org/understanding-json-schema/reference/generic#constant-values Constant values
 * @see https://json-schema.org/understanding-json-schema/reference/generic#enumerated-values Enumerated values
 */
trait ComparesJsonValues
{
    /**
     * Check if two values are equal according to JSON Schema equality rules.
     *
     * In JSON Schema, numeric values are compared by mathematical value, not by
     * PHP type. Thus 0 equals 0.0, and 1 equals 1.0. Arrays are compared recursively
     * with key order independence for objects (associative arrays).
     *
     * This implements the equality semantics required by const, enum, and uniqueItems
     * keywords per the JSON Schema specification.
     *
     * @param mixed $a First value to compare
     * @param mixed $b Second value to compare
     *
     * @return bool True if values are equal per JSON Schema semantics, false otherwise
     */
    protected function jsonEquals(mixed $a, mixed $b): bool
    {
        // For numbers, compare by value (0 == 0.0)
        // Cast to float to handle int/float equivalence with strict comparison
        if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
            return (float) $a === (float) $b;
        }

        // For arrays, recursively compare elements
        if (is_array($a) && is_array($b)) {
            // Different lengths means not equal
            if (count($a) !== count($b)) {
                return false;
            }

            // Get and sort keys for comparison (JSON object key order doesn't matter)
            $keysA = array_keys($a);
            $keysB = array_keys($b);
            sort($keysA);
            sort($keysB);

            if ($keysA !== $keysB) {
                return false;
            }

            return array_all($keysA, fn ($key) => $this->jsonEquals($a[$key], $b[$key]));
        }

        // For all other types, use strict comparison
        return $a === $b;
    }
}
