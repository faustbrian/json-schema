<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function is_array;

/**
 * Merges multiple JSON schemas into a single schema.
 *
 * Combines schemas while preserving constraints and maintaining validity.
 * Useful for composing schemas from multiple sources or creating schema
 * hierarchies.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/understanding-json-schema/reference/combining Combining Schemas
 */
final class SchemaMerger
{
    /**
     * Merge multiple schemas using allOf composition.
     *
     * Combines schemas so that data must validate against ALL schemas.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public static function mergeAllOf(array $schemas): array
    {
        if ($schemas === []) {
            return ['type' => 'null'];
        }

        if (count($schemas) === 1) {
            return $schemas[0];
        }

        return ['allOf' => array_values($schemas)];
    }

    /**
     * Merge multiple schemas using anyOf composition.
     *
     * Combines schemas so that data must validate against ANY schema.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public static function mergeAnyOf(array $schemas): array
    {
        if ($schemas === []) {
            return ['type' => 'null'];
        }

        if (count($schemas) === 1) {
            return $schemas[0];
        }

        return ['anyOf' => array_values($schemas)];
    }

    /**
     * Merge multiple schemas using oneOf composition.
     *
     * Combines schemas so that data must validate against EXACTLY ONE schema.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    public static function mergeOneOf(array $schemas): array
    {
        if ($schemas === []) {
            return ['type' => 'null'];
        }

        if (count($schemas) === 1) {
            return $schemas[0];
        }

        return ['oneOf' => array_values($schemas)];
    }

    /**
     * Deep merge two schemas.
     *
     * Intelligently combines schemas by merging properties, constraints,
     * and other keywords. Uses allOf for conflicting constraints.
     *
     * @param array<string, mixed> $schema1 First schema
     * @param array<string, mixed> $schema2 Second schema
     *
     * @return array<string, mixed> Merged schema
     */
    public static function deepMerge(array $schema1, array $schema2): array
    {
        $result = [];

        foreach (array_unique([...array_keys($schema1), ...array_keys($schema2)]) as $key) {
            if (isset($schema1[$key], $schema2[$key])) {
                // Both have this key - merge intelligently
                if ($key === 'properties' && is_array($schema1[$key]) && is_array($schema2[$key])) {
                    $result[$key] = array_merge($schema1[$key], $schema2[$key]);
                } elseif ($key === 'required' && is_array($schema1[$key]) && is_array($schema2[$key])) {
                    $result[$key] = array_values(array_unique([...$schema1[$key], ...$schema2[$key]]));
                } elseif ($schema1[$key] !== $schema2[$key]) {
                    // For conflicting values, use allOf
                    $result['allOf'] = [
                        [$key => $schema1[$key]],
                        [$key => $schema2[$key]],
                    ];
                } else {
                    $result[$key] = $schema1[$key];
                }
            } elseif (isset($schema1[$key])) {
                $result[$key] = $schema1[$key];
            } else {
                $result[$key] = $schema2[$key];
            }
        }

        return $result;
    }
}
