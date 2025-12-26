<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Contracts;

/**
 * Contract for immutable JSON Schema representation.
 *
 * Defines the interface for working with JSON Schema documents as immutable
 * value objects. Implementations should provide read-only access to the schema
 * data and validation against the schema.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/draft/2020-12/json-schema-core
 * @see https://json-schema.org/understanding-json-schema/reference/schema
 */
interface SchemaInterface
{
    /**
     * Get the complete schema as an array.
     *
     * Returns the entire JSON Schema document as an associative array,
     * preserving the original structure and all keywords.
     *
     * @return array<string, mixed> The schema data
     */
    public function toArray(): array;

    /**
     * Get a specific property from the schema.
     *
     * Retrieves a top-level property from the schema by key. Returns null
     * if the property does not exist.
     *
     * @param string $key The property key to retrieve
     *
     * @return mixed The property value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Check if a property exists in the schema.
     *
     * Determines whether a top-level property with the given key exists
     * in the schema.
     *
     * @param string $key The property key to check
     *
     * @return bool True if the property exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Get the JSON representation of the schema.
     *
     * Serializes the schema to a JSON string. Useful for persisting or
     * transmitting the schema.
     *
     * @param int $flags JSON encoding flags (e.g., JSON_PRETTY_PRINT)
     *
     * @return string The JSON-encoded schema
     */
    public function toJson(int $flags = 0): string;
}
