<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use Cline\JsonSchema\Support\JsonDecoder;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

use function array_any;
use function array_keys;
use function assert;
use function count;
use function floor;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_object;
use function is_string;
use function range;
use function sprintf;

/**
 * Type validation support for JSON Schema.
 *
 * Implements JSON type validation with proper distinction between JSON types and
 * PHP types. Handles edge cases like distinguishing empty objects {} from empty
 * arrays [], treating floats with zero fractional parts as integers (Draft 06+),
 * and supporting bignum integers that exceed PHP_INT_MAX.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/type Understanding JSON Schema - Type
 * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.5 Draft-04 - Type Validation
 * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.1.1 Draft-06 - Type Validation
 * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.1.1 Draft-07 - Type Validation
 * @see https://json-schema.org/draft/2019-09/json-schema-validation#rfc.section.6.1.1 Draft 2019-09 - Type Validation
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-any Draft 2020-12 - Type Validation
 */
trait ValidatesTypes
{
    /**
     * Validate the type keyword (JSON type constraint).
     *
     * Validates that the instance matches one of the allowed JSON types: null, boolean,
     * object, array, number, integer, or string. Supports both single type strings and
     * arrays of allowed types. Properly distinguishes JSON objects from JSON arrays
     * using decoder markers.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/type
     * @param  mixed                $data   The instance to validate against the type constraint
     * @param  array<string, mixed> $schema The schema definition containing the type constraint
     * @return bool                 True if the instance matches at least one allowed type, false otherwise
     */
    protected function validateType(mixed $data, array $schema): bool
    {
        if (!isset($schema['type'])) {
            return true;
        }

        $allowedTypes = is_array($schema['type']) ? $schema['type'] : [$schema['type']];

        $matches = array_any($allowedTypes, function ($type) use ($data) {
            assert(is_string($type));

            return $this->matchesType($data, $type);
        });

        if (!$matches) {
            $actualType = $this->getActualType($data);
            $schemaType = $schema['type'];
            $expected = is_array($schemaType) ? implode(', ', $schemaType) : $schemaType;
            assert(is_string($expected));
            $this->addError('type', sprintf('Value type %s does not match expected type(s): %s', $actualType, $expected));

            return false;
        }

        return true;
    }

    /**
     * Check if an instance matches a specific JSON Schema type.
     *
     * Performs type matching for a single JSON type using proper JSON semantics.
     * Handles special cases: integer matching for floats with zero fractional part
     * (Draft 06+), bignum integer support, and proper object/array distinction using
     * JsonDecoder markers.
     *
     * @param mixed  $data The instance to check for type matching
     * @param string $type The JSON Schema type to match against (null, boolean, object, array, number, integer, string)
     *
     * @return bool True if the instance matches the specified type, false otherwise
     */
    protected function matchesType(mixed $data, string $type): bool
    {
        return match ($type) {
            'null' => null === $data,
            'boolean' => is_bool($data),
            'object' => is_object($data) || $this->matchesObjectType($data),
            'array' => $this->matchesArrayType($data),
            'number' => is_int($data) || is_float($data),
            'integer' => is_int($data) || $this->isIntegerFloat($data),
            'string' => is_string($data),
            default => false,
        };
    }

    /**
     * Check if array is associative (object-like).
     *
     * @param array<mixed> $array The array to check
     *
     * @return bool True if associative
     */
    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Get the actual JSON Schema type of a value.
     *
     * @param mixed $data The value to get the type of
     *
     * @return string The JSON Schema type name
     */
    private function getActualType(mixed $data): string
    {
        if ($data === null) {
            return 'null';
        }

        if (is_bool($data)) {
            return 'boolean';
        }

        if (is_int($data)) {
            return 'integer';
        }

        if (is_float($data)) {
            return 'number';
        }

        if (is_string($data)) {
            return 'string';
        }

        if (is_array($data)) {
            return $this->isJsonObject($data) ? 'object' : 'array';
        }

        return 'unknown';
    }

    /**
     * Determine if a float value should be treated as an integer.
     *
     * Checks if a float represents an integer value (no fractional part) and exceeds
     * PHP's integer range, making it a bignum. Draft 04 only accepts bignums; Draft 06+
     * accepts any float with zero fractional part (like 1.0). Validators override this
     * method to implement draft-specific behavior.
     *
     * @param mixed $data The value to check for integer representation
     *
     * @return bool True if it's a bignum exceeding PHP integer range with no fractional part
     */
    private function isIntegerFloat(mixed $data): bool
    {
        if (!is_float($data)) {
            return false;
        }

        // Check if the float has no fractional part
        if (floor($data) !== $data || is_nan($data) || is_infinite($data)) {
            return false;
        }

        // Only accept floats that EXCEED the PHP integer range (bignums)
        // Regular floats like 1.0 should NOT be accepted as integers
        return $data > PHP_INT_MAX || $data < PHP_INT_MIN;
    }

    /**
     * Check if data matches the 'object' type.
     *
     * @param mixed $data The data to check
     *
     * @return bool True if it matches object type
     */
    private function matchesObjectType(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Check for empty object marker (from JsonDecoder)
        if (JsonDecoder::isEmptyObject($data)) {
            return true;
        }

        // Empty arrays (without marker) are JSON arrays
        if ($data === []) {
            return false;
        }

        // Non-empty associative arrays are objects
        return $this->isAssociativeArray($data);
    }

    /**
     * Check if data matches the 'array' type.
     *
     * @param mixed $data The data to check
     *
     * @return bool True if it matches array type
     */
    private function matchesArrayType(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Empty object markers are JSON objects, not arrays
        if (JsonDecoder::isEmptyObject($data)) {
            return false;
        }

        // Empty arrays (without marker) are JSON arrays
        if ($data === []) {
            return true;
        }

        // Non-empty sequential arrays are JSON arrays
        return !$this->isAssociativeArray($data);
    }
}
