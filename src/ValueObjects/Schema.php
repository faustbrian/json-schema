<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\ValueObjects;

use Cline\JsonSchema\Contracts\SchemaInterface;
use Cline\JsonSchema\Exceptions\InvalidJsonSchemaException;

use function array_key_exists;
use function is_array;
use function json_decode;
use function json_encode;

/**
 * Immutable JSON Schema value object.
 *
 * Represents a JSON Schema document as an immutable value object that encapsulates
 * schema data in a type-safe, readonly structure. Once created, the schema cannot
 * be modified, ensuring consistency throughout the validation process. Provides
 * methods for schema creation, property access, and serialization.
 *
 * The value object pattern ensures that schema documents maintain their integrity
 * and can be safely shared across validation operations without risk of mutation.
 * Supports creation from JSON strings and conversion back to JSON or array formats.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/understanding-json-schema/reference/schema JSON Schema Structure and Keywords
 * @see SchemaInterface The contract this value object implements
 */
final readonly class Schema implements SchemaInterface
{
    /**
     * Create a new schema instance.
     *
     * @param array<string, mixed> $data The JSON Schema data as an associative array containing
     *                                   keywords, validation rules, and metadata that define
     *                                   the schema structure and constraints
     */
    public function __construct(
        private array $data,
    ) {}

    /**
     * Create a schema from a JSON string.
     *
     * Parses a JSON-encoded string into a schema instance. The JSON must decode
     * to an associative array representing the schema document. This factory method
     * enables schema creation from stored JSON files or API responses.
     *
     * @param string $json The JSON-encoded string representing the schema document
     *
     * @throws InvalidJsonSchemaException If JSON is invalid or does not decode to an array
     *
     * @return self The schema instance created from the parsed JSON
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw InvalidJsonSchemaException::fromReason('JSON must decode to an array');
        }

        return new self($data);
    }

    /**
     * Get the complete schema as an array.
     *
     * Returns the entire JSON Schema document as an associative array,
     * preserving the original structure including all keywords, validation
     * rules, and metadata. Useful for schema manipulation or inspection.
     *
     * @return array<string, mixed> The complete schema data structure
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get a specific property from the schema.
     *
     * Retrieves a top-level property value from the schema by key. This method
     * provides convenient access to schema keywords like "type", "properties",
     * "required", etc. Returns null if the property does not exist in the schema.
     *
     * @param string $key The property key to retrieve (e.g., "type", "properties")
     *
     * @return mixed The property value if it exists, null otherwise
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check if a property exists in the schema.
     *
     * Determines whether a top-level property with the given key exists in the
     * schema. This uses array_key_exists to distinguish between a property set
     * to null versus a property that does not exist at all.
     *
     * @param string $key The property key to check for existence
     *
     * @return bool True if the property exists in the schema, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get the JSON representation of the schema.
     *
     * Serializes the schema to a JSON-encoded string. Useful for persisting
     * the schema to storage, transmitting over HTTP, or generating schema
     * documentation. Supports standard JSON encoding flags for formatting.
     *
     * @param int $flags Optional JSON encoding flags such as JSON_PRETTY_PRINT,
     *                   JSON_UNESCAPED_SLASHES, etc. Defaults to 0 (compact output)
     *
     * @throws InvalidJsonSchemaException If JSON encoding fails
     *
     * @return string The JSON-encoded representation of the schema
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->data, $flags);

        if ($json === false) {
            throw InvalidJsonSchemaException::fromReason('Failed to encode schema to JSON');
        }

        return $json;
    }
}
