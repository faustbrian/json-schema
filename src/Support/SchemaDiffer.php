<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function array_diff_key;
use function array_intersect_key;
use function array_keys;

/**
 * Compares JSON schemas and identifies differences.
 *
 * Analyzes two schemas to find added, removed, and modified constraints.
 * Useful for schema versioning, migration planning, and breaking change
 * detection.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SchemaDiffer
{
    /**
     * Compare two schemas and return differences.
     *
     * Analyzes schemas and returns added, removed, and changed keywords.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return array{added: array<string, mixed>, removed: array<string, mixed>, changed: array<string, array{old: mixed, new: mixed}>} Schema differences
     */
    public static function diff(array $oldSchema, array $newSchema): array
    {
        return [
            'added' => self::getAdded($oldSchema, $newSchema),
            'removed' => self::getRemoved($oldSchema, $newSchema),
            'changed' => self::getChanged($oldSchema, $newSchema),
        ];
    }

    /**
     * Check if schema changes are breaking.
     *
     * Identifies changes that would invalidate previously valid data.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return bool True if changes are breaking
     */
    public static function hasBreakingChanges(array $oldSchema, array $newSchema): bool
    {
        $diff = self::diff($oldSchema, $newSchema);

        // Adding required fields is breaking
        if (isset($diff['changed']['required'])) {
            return true;
        }

        // Making types more restrictive is breaking
        if (isset($diff['changed']['type'])) {
            return true;
        }

        // Adding minimum/maximum constraints is breaking
        return isset($diff['added']['minimum']) || isset($diff['added']['maximum']);
    }

    /**
     * Get keywords added in new schema.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return array<string, mixed> Added keywords and values
     */
    private static function getAdded(array $oldSchema, array $newSchema): array
    {
        return array_diff_key($newSchema, $oldSchema);
    }

    /**
     * Get keywords removed in new schema.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return array<string, mixed> Removed keywords and old values
     */
    private static function getRemoved(array $oldSchema, array $newSchema): array
    {
        return array_diff_key($oldSchema, $newSchema);
    }

    /**
     * Get keywords changed between schemas.
     *
     * @param array<string, mixed> $oldSchema Original schema
     * @param array<string, mixed> $newSchema Updated schema
     *
     * @return array<string, array{old: mixed, new: mixed}> Changed keywords with old and new values
     */
    private static function getChanged(array $oldSchema, array $newSchema): array
    {
        $changed = [];
        $common = array_intersect_key($oldSchema, $newSchema);

        foreach (array_keys($common) as $key) {
            if ($oldSchema[$key] === $newSchema[$key]) {
                continue;
            }

            $changed[$key] = [
                'old' => $oldSchema[$key],
                'new' => $newSchema[$key],
            ];
        }

        return $changed;
    }
}
