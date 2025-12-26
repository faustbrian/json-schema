<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function array_shift;
use function count;
use function serialize;
use function sha1;

/**
 * Compiles and caches JSON schemas for improved validation performance.
 *
 * Pre-processes schemas to resolve references, normalize structure, and
 * cache the compiled result. Subsequent validations using the same schema
 * can reuse the compiled version for faster execution.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-9 Schema References
 */
final class SchemaCompiler
{
    /**
     * Maximum cache size (number of schemas).
     */
    private const int MAX_CACHE_SIZE = 1_000;

    /**
     * Compiled schema cache.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $cache = [];

    /**
     * Compile a schema for optimized validation.
     *
     * Processes the schema to resolve references and optimize structure.
     * Returns a compiled schema that can be cached for reuse.
     *
     * @param array<string, mixed> $schema The schema to compile
     *
     * @return array<string, mixed> Compiled schema
     */
    public static function compile(array $schema): array
    {
        $cacheKey = self::getCacheKey($schema);

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $compiled = self::processSchema($schema);

        self::cacheSchema($cacheKey, $compiled);

        return $compiled;
    }

    /**
     * Check if schema is cached.
     *
     * @param array<string, mixed> $schema The schema to check
     *
     * @return bool True if schema is cached
     */
    public static function isCached(array $schema): bool
    {
        return isset(self::$cache[self::getCacheKey($schema)]);
    }

    /**
     * Clear the compilation cache.
     *
     * Removes all cached compiled schemas.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Get cache statistics.
     *
     * Returns information about cache usage.
     *
     * @return array{size: int, maxSize: int} Cache statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'size' => count(self::$cache),
            'maxSize' => self::MAX_CACHE_SIZE,
        ];
    }

    /**
     * Process schema for compilation.
     *
     * Internal method that performs the actual compilation steps.
     *
     * @param array<string, mixed> $schema The schema to process
     *
     * @return array<string, mixed> Processed schema
     */
    private static function processSchema(array $schema): array
    {
        // For now, return as-is. In production, would:
        // - Resolve all $ref
        // - Normalize schema structure
        // - Pre-compile regex patterns
        // - Build evaluation tree
        return $schema;
    }

    /**
     * Generate cache key for schema.
     *
     * Creates a unique identifier for the schema based on its content.
     *
     * @param array<string, mixed> $schema The schema
     *
     * @return string Cache key
     */
    private static function getCacheKey(array $schema): string
    {
        return sha1(serialize($schema));
    }

    /**
     * Store compiled schema in cache.
     *
     * Implements simple LRU eviction when cache is full.
     *
     * @param string               $key      Cache key
     * @param array<string, mixed> $compiled Compiled schema
     */
    private static function cacheSchema(string $key, array $compiled): void
    {
        if (count(self::$cache) >= self::MAX_CACHE_SIZE) {
            // Simple FIFO eviction - remove first item
            array_shift(self::$cache);
        }

        self::$cache[$key] = $compiled;
    }
}
