<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function is_array;
use function is_object;
use function json_decode;

/**
 * JSON decoder that preserves distinction between empty objects and empty arrays.
 *
 * Standard json_decode($json, true) converts both {} and [] to PHP empty arrays [],
 * losing the distinction between JSON objects and JSON arrays. This is problematic
 * for JSON Schema validation which treats objects and arrays differently.
 *
 * This decoder uses a special marker to preserve the {} vs [] distinction during
 * the conversion to associative arrays, ensuring schema validators can properly
 * distinguish between empty objects and empty arrays.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-4.2.1 Instance data model
 * @see https://json-schema.org/understanding-json-schema/reference/type Type-specific validation
 * @see https://datatracker.ietf.org/doc/html/rfc8259 JSON data interchange format (RFC 8259)
 */
final class JsonDecoder
{
    /**
     * Special marker used internally to represent empty JSON objects {}.
     *
     * When an empty object {} is encountered, it's converted to
     * ['__EMPTY_JSON_OBJECT__' => true] to distinguish it from
     * an empty array [] which remains as [].
     */
    private const string EMPTY_OBJECT_MARKER = '__EMPTY_JSON_OBJECT__';

    /**
     * Decode JSON string while preserving empty object vs empty array distinction.
     *
     * Unlike standard json_decode($json, true) which converts both {} and [] to [],
     * this method marks empty objects with a special marker array so validators can
     * properly distinguish between JSON object and array types.
     *
     * @param string $json JSON string to decode
     *
     * @return mixed Decoded data with empty objects marked as ['__EMPTY_JSON_OBJECT__' => true]
     */
    public static function decode(string $json): mixed
    {
        // Decode without associative flag to get stdClass for objects
        $data = json_decode($json, false);

        // Convert to associative arrays while preserving empty object markers
        return self::convertToAssociative($data);
    }

    /**
     * Check if a value represents an empty JSON object marker.
     *
     * Tests whether the given value is the special marker array used to represent
     * an empty JSON object {} (as opposed to an empty JSON array []).
     *
     * @param mixed $value Value to check
     *
     * @return bool True if value is ['__EMPTY_JSON_OBJECT__' => true], false otherwise
     */
    public static function isEmptyObject(mixed $value): bool
    {
        return is_array($value)
            && isset($value[self::EMPTY_OBJECT_MARKER])
            && $value[self::EMPTY_OBJECT_MARKER] === true;
    }

    /**
     * Convert stdClass objects to associative arrays recursively.
     *
     * Traverses the decoded JSON data structure and converts all stdClass objects
     * to associative arrays. Empty stdClass objects (representing empty JSON objects {})
     * are marked with a special marker ['__EMPTY_JSON_OBJECT__' => true] to distinguish
     * them from empty arrays [].
     *
     * @param mixed $data Data to convert (from json_decode with associative=false)
     *
     * @return mixed Converted data structure with objects as arrays and empty objects marked
     */
    private static function convertToAssociative(mixed $data): mixed
    {
        // Handle objects (stdClass)
        if (is_object($data)) {
            $array = (array) $data;

            // Empty object - add marker
            if ($array === []) {
                return [self::EMPTY_OBJECT_MARKER => true];
            }

            // Non-empty object - convert properties recursively
            $result = [];

            foreach ($array as $key => $value) {
                $result[$key] = self::convertToAssociative($value);
            }

            return $result;
        }

        // Handle arrays
        if (is_array($data)) {
            // Empty array stays as []
            if ($data === []) {
                return [];
            }

            // Non-empty array - convert elements recursively
            $result = [];

            foreach ($data as $key => $value) {
                $result[$key] = self::convertToAssociative($value);
            }

            return $result;
        }

        // Scalar values pass through
        return $data;
    }
}
