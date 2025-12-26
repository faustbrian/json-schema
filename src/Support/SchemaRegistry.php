<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Contracts\SchemaInterface;

use function array_key_exists;

/**
 * Registry for caching resolved schema instances by URI.
 *
 * Provides a cache for storing and retrieving schema instances by their $id URI.
 * This prevents redundant parsing and resolution of schema documents, improving
 * performance when the same schema is referenced multiple times during validation.
 *
 * The registry is essential for proper $ref resolution, allowing validators to
 * look up schemas by their canonical URIs as specified in the $id keyword.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.1 $id keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.2 $anchor keyword
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8 Schema identification
 * @see https://json-schema.org/understanding-json-schema/structuring Schema identification and reuse
 */
final class SchemaRegistry
{
    /**
     * The schema cache.
     *
     * @var array<string, SchemaInterface>
     */
    private array $schemas = [];

    /**
     * Register a schema in the registry.
     *
     * Stores a schema instance in the cache, associated with the given URI.
     * If a schema with the same URI already exists, it will be replaced.
     *
     * @param string          $uri    The schema URI (typically the $id or $schema value)
     * @param SchemaInterface $schema The schema instance to register
     */
    public function register(string $uri, SchemaInterface $schema): void
    {
        $this->schemas[$uri] = $schema;
    }

    /**
     * Get a schema from the registry.
     *
     * Retrieves a previously registered schema by URI. Returns null if
     * no schema with the given URI has been registered.
     *
     * @param string $uri The schema URI to retrieve
     *
     * @return null|SchemaInterface The cached schema or null if not found
     */
    public function get(string $uri): ?SchemaInterface
    {
        return $this->schemas[$uri] ?? null;
    }

    /**
     * Check if a schema is registered.
     *
     * Determines whether a schema with the given URI has been registered.
     *
     * @param string $uri The schema URI to check
     *
     * @return bool True if the schema is registered, false otherwise
     */
    public function has(string $uri): bool
    {
        return array_key_exists($uri, $this->schemas);
    }

    /**
     * Remove a schema from the registry.
     *
     * Deletes a cached schema by URI. Does nothing if the schema is not
     * registered.
     *
     * @param string $uri The schema URI to remove
     */
    public function remove(string $uri): void
    {
        unset($this->schemas[$uri]);
    }

    /**
     * Clear all schemas from the registry.
     *
     * Removes all cached schemas, resetting the registry to an empty state.
     */
    public function clear(): void
    {
        $this->schemas = [];
    }
}
