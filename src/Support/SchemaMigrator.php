<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Enums\Draft;

/**
 * Migrates schemas between draft versions.
 *
 * Converts schemas from one JSON Schema draft version to another,
 * updating keywords and structure to match the target specification.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/release-notes Release Notes for Changes
 */
final class SchemaMigrator
{
    /**
     * Migrate schema to a different draft version.
     *
     * Converts schema structure and keywords to match the target draft.
     *
     * @param array<string, mixed> $schema      The schema to migrate
     * @param Draft                $targetDraft The target draft version
     *
     * @return array<string, mixed> Migrated schema
     */
    public static function migrate(array $schema, Draft $targetDraft): array
    {
        $migrated = $schema;

        // Update $schema keyword
        $migrated['$schema'] = $targetDraft->value;

        // Apply draft-specific migrations
        return match ($targetDraft) {
            Draft::Draft202012 => self::migrateTo2020_12($migrated),
            Draft::Draft201909 => self::migrateTo2019_09($migrated),
            Draft::Draft07 => self::migrateTo07($migrated),
            Draft::Draft06 => self::migrateTo06($migrated),
            Draft::Draft04 => self::migrateTo04($migrated),
        };
    }

    /**
     * Migrate to Draft 2020-12.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return array<string, mixed> Migrated schema
     */
    private static function migrateTo2020_12(array $schema): array
    {
        // Convert $recursiveRef to $dynamicRef if present
        if (isset($schema['$recursiveRef'])) {
            $schema['$dynamicRef'] = $schema['$recursiveRef'];
            unset($schema['$recursiveRef']);
        }

        // Convert $recursiveAnchor to $dynamicAnchor
        if (isset($schema['$recursiveAnchor'])) {
            $schema['$dynamicAnchor'] = $schema['$recursiveAnchor'];
            unset($schema['$recursiveAnchor']);
        }

        return $schema;
    }

    /**
     * Migrate to Draft 2019-09.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return array<string, mixed> Migrated schema
     */
    private static function migrateTo2019_09(array $schema): array
    {
        // Convert definitions to $defs
        if (isset($schema['definitions']) && !isset($schema['$defs'])) {
            $schema['$defs'] = $schema['definitions'];
            unset($schema['definitions']);
        }

        return $schema;
    }

    /**
     * Migrate to Draft 07.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return array<string, mixed> Migrated schema
     */
    private static function migrateTo07(array $schema): array
    {
        // Draft 07 uses definitions
        if (isset($schema['$defs']) && !isset($schema['definitions'])) {
            $schema['definitions'] = $schema['$defs'];
            unset($schema['$defs']);
        }

        return $schema;
    }

    /**
     * Migrate to Draft 06.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return array<string, mixed> Migrated schema
     */
    private static function migrateTo06(array $schema): array
    {
        return self::migrateTo07($schema);
    }

    /**
     * Migrate to Draft 04.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return array<string, mixed> Migrated schema
     */
    private static function migrateTo04(array $schema): array
    {
        return self::migrateTo07($schema);
    }
}
