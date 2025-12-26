<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use function abs;
use function assert;
use function fmod;
use function is_finite;
use function is_float;
use function is_infinite;
use function is_int;
use function json_encode;
use function log;
use function round;
use function sprintf;

/**
 * Numeric validation support for JSON Schema.
 *
 * Implements validation constraints for numeric types including minimum and maximum
 * bounds (both inclusive and exclusive), and multipleOf divisibility requirements.
 * Handles edge cases like floating-point precision, infinity overflow, and power-of-two
 * validation for large magnitude numbers.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/numeric Understanding JSON Schema - Numeric Types
 * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.1 Draft-04 - Numeric Validation
 * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.2 Draft-06 - Numeric Validation
 * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.2 Draft-07 - Numeric Validation
 * @see https://json-schema.org/draft/2019-09/json-schema-validation#rfc.section.6.2 Draft 2019-09 - Numeric Validation
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-num Draft 2020-12 - Numeric Validation
 */
trait ValidatesNumbers
{
    /**
     * Validate the minimum keyword (inclusive lower bound).
     *
     * Validates that numeric values meet or exceed the specified minimum value.
     * This keyword only applies to numeric types (integer and number); non-numeric
     * values automatically pass validation.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-minimum Draft 2020-12 - minimum
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the minimum constraint
     * @return bool                 True if the value is greater than or equal to minimum, false otherwise
     */
    protected function validateMinimum(mixed $data, array $schema): bool
    {
        if (!isset($schema['minimum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        if ($data < $schema['minimum']) {
            $this->addError('minimum', sprintf('Value %s is less than minimum %s', json_encode($data), json_encode($schema['minimum'])));

            return false;
        }

        return true;
    }

    /**
     * Validate the maximum keyword (inclusive upper bound).
     *
     * Validates that numeric values do not exceed the specified maximum value.
     * This keyword only applies to numeric types; non-numeric values pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-maximum Draft 2020-12 - maximum
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the maximum constraint
     * @return bool                 True if the value is less than or equal to maximum, false otherwise
     */
    protected function validateMaximum(mixed $data, array $schema): bool
    {
        if (!isset($schema['maximum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        return $data <= $schema['maximum'];
    }

    /**
     * Validate the exclusiveMinimum keyword (exclusive lower bound).
     *
     * Validates that numeric values are strictly greater than the specified minimum.
     * Unlike minimum, the boundary value itself is not valid. Only applies to numeric types.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.2.5 Draft-06 - exclusiveMinimum
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-exclusiveminimum Draft 2020-12 - exclusiveMinimum
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the exclusiveMinimum constraint
     * @return bool                 True if the value is strictly greater than exclusiveMinimum, false otherwise
     */
    protected function validateExclusiveMinimum(mixed $data, array $schema): bool
    {
        if (!isset($schema['exclusiveMinimum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        return $data > $schema['exclusiveMinimum'];
    }

    /**
     * Validate the exclusiveMaximum keyword (exclusive upper bound).
     *
     * Validates that numeric values are strictly less than the specified maximum.
     * Unlike maximum, the boundary value itself is not valid. Only applies to numeric types.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.2.3 Draft-06 - exclusiveMaximum
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-exclusivemaximum Draft 2020-12 - exclusiveMaximum
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the exclusiveMaximum constraint
     * @return bool                 True if the value is strictly less than exclusiveMaximum, false otherwise
     */
    protected function validateExclusiveMaximum(mixed $data, array $schema): bool
    {
        if (!isset($schema['exclusiveMaximum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        return $data < $schema['exclusiveMaximum'];
    }

    /**
     * Validate the multipleOf keyword (divisibility constraint).
     *
     * Validates that the numeric value is divisible by the specified divisor with
     * no remainder. Uses floating-point tolerance (1e-10) to handle precision issues.
     * Includes special handling for overflow to infinity and power-of-two divisors
     * at large magnitudes where all representable floats are multiples.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#multiples Understanding JSON Schema - Multiples
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-multipleof Draft 2020-12 - multipleOf
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the multipleOf constraint
     * @return bool                 True if the value is an exact multiple of the specified divisor, false otherwise
     */
    protected function validateMultipleOf(mixed $data, array $schema): bool
    {
        if (!isset($schema['multipleOf']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        $multipleOf = $schema['multipleOf'];
        assert(is_int($multipleOf) || is_float($multipleOf));

        $quotient = $data / $multipleOf;

        // Handle overflow to infinity
        // Special case: if multipleOf is a power of 2, we can validate even with overflow
        // because at large magnitudes, all representable floats are multiples of small powers of 2
        if (is_infinite($quotient)) {
            return $this->isFiniteAndPowerOfTwo($multipleOf);
        }

        $remainder = fmod($quotient, 1.0);

        return abs($remainder) < 1e-10;
    }

    /**
     * Check if a numeric value is finite and represents a power of two.
     *
     * Used by multipleOf validation to detect when division overflow to infinity
     * can still be validated correctly. At large magnitudes, all representable
     * floating-point numbers are multiples of small powers of two due to how
     * IEEE 754 floating-point representation works.
     *
     * @param mixed $value The numeric value to check for power-of-two property
     *
     * @return bool True if the value is finite, positive, and a power of two
     */
    private function isFiniteAndPowerOfTwo(mixed $value): bool
    {
        if (!is_int($value) && !is_float($value)) {
            return false;
        }

        if (!is_finite($value) || $value <= 0) {
            return false;
        }

        // For powers of 2, log2 should be an integer (within floating point precision)
        $log2 = log($value, 2);

        return abs($log2 - round($log2)) < 1e-10;
    }
}
