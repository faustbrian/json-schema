<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Generators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\ValueObjects\Schema;

use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_URL;
use const SORT_REGULAR;

use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function filter_var;
use function get_object_vars;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function preg_match;
use function range;

/**
 * Generates JSON Schema definitions from data samples.
 *
 * Analyzes data structures and infers appropriate JSON Schema constraints.
 * Supports generating schemas for primitives, objects, arrays, and nested
 * structures. The generator makes best-effort type inference based on the
 * provided data samples.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema
 * @see https://json-schema.org/learn/getting-started-step-by-step Creating Schemas
 */
final class SchemaGenerator
{
    /**
     * Generate a JSON Schema from data sample.
     *
     * Analyzes the provided data and generates a schema that would validate
     * that data. For objects and arrays, recursively generates schemas for
     * nested structures.
     *
     * @param mixed $data  The data to generate a schema from
     * @param Draft $draft The JSON Schema draft version to generate
     *
     * @return Schema Generated schema that validates the input data
     */
    public static function generate(mixed $data, Draft $draft = Draft::Draft202012): Schema
    {
        $schemaData = self::generateSchemaArray($data);
        $schemaData['$schema'] = $draft->value;

        return new Schema($schemaData);
    }

    /**
     * Generate schema from multiple data samples.
     *
     * Analyzes multiple data samples and generates a schema that would
     * validate all of them. Useful for creating schemas from a dataset.
     *
     * @param array<mixed> $samples Array of data samples to analyze
     * @param Draft        $draft   The JSON Schema draft version to generate
     *
     * @return Schema Generated schema that validates all samples
     */
    public static function generateFromSamples(array $samples, Draft $draft = Draft::Draft202012): Schema
    {
        if ($samples === []) {
            return new Schema(['$schema' => $draft->value, 'type' => 'null']);
        }

        // Generate schema for each sample
        $schemas = array_map(
            self::generateSchemaArray(...),
            $samples,
        );

        // Merge schemas to find common structure
        $mergedSchema = self::mergeSchemas($schemas);
        $mergedSchema['$schema'] = $draft->value;

        return new Schema($mergedSchema);
    }

    /**
     * Generate schema array from data.
     *
     * Internal method that builds the schema structure without the $schema
     * keyword, allowing for recursive schema generation.
     *
     * @param mixed $data The data to analyze
     *
     * @return array<string, mixed> Schema definition as array
     */
    private static function generateSchemaArray(mixed $data): array
    {
        return match (true) {
            null === $data => ['type' => 'null'],
            is_bool($data) => ['type' => 'boolean'],
            is_int($data) => ['type' => 'integer'],
            is_float($data) => ['type' => 'number'],
            is_string($data) => self::generateStringSchema($data),
            is_array($data) => self::generateArraySchema($data),
            is_object($data) => self::generateObjectSchema($data),
            default => ['type' => 'string'],
        };
    }

    /**
     * Generate schema for string values.
     *
     * Analyzes string content and adds format constraints for common
     * patterns like emails, URIs, dates, etc.
     *
     * @param string $value The string value to analyze
     *
     * @return array<string, mixed> String schema with optional format
     */
    private static function generateStringSchema(string $value): array
    {
        $schema = ['type' => 'string'];

        // Detect common formats
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $schema['format'] = 'email';
        } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            $schema['format'] = 'uri';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $schema['format'] = 'date';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
            $schema['format'] = 'date-time';
        } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $schema['format'] = 'uuid';
        }

        return $schema;
    }

    /**
     * Generate schema for array values.
     *
     * Determines if the array is a list (numeric keys) or object (string keys)
     * and generates appropriate schema constraints.
     *
     * @param array<mixed> $value The array to analyze
     *
     * @return array<string, mixed> Array or object schema
     */
    private static function generateArraySchema(array $value): array
    {
        // Empty array - default to array type
        if ($value === []) {
            return ['type' => 'array'];
        }

        // Check if this is an associative array (object) or indexed array
        $keys = array_keys($value);
        $isSequential = $keys === range(0, count($value) - 1);

        if ($isSequential) {
            // It's an array - analyze item types
            return self::generateListSchema($value);
        }

        // It's an object - analyze properties
        /** @var array<string, mixed> $value */
        return self::generateObjectSchemaFromArray($value);
    }

    /**
     * Generate schema for list (sequential array).
     *
     * Analyzes array items and determines if they're all the same type
     * or mixed types.
     *
     * @param array<mixed> $items The array items to analyze
     *
     * @return array<string, mixed> Array schema with items constraint
     */
    private static function generateListSchema(array $items): array
    {
        $schema = ['type' => 'array'];

        if ($items === []) {
            return $schema;
        }

        // Analyze all items to determine if they share a common type
        $itemSchemas = array_map(
            self::generateSchemaArray(...),
            $items,
        );

        // If all items have the same type, use single items schema
        $types = array_unique(array_map(
            static fn (array $s): string => is_string($s['type'] ?? null) ? $s['type'] : 'mixed',
            $itemSchemas,
        ));

        if (count($types) === 1) {
            $schema['items'] = $itemSchemas[0];
        } else {
            // Mixed types - use anyOf
            $schema['items'] = [
                'anyOf' => array_values(array_unique($itemSchemas, SORT_REGULAR)),
            ];
        }

        return $schema;
    }

    /**
     * Generate schema for object (associative array).
     *
     * Analyzes object properties and generates schema for each property.
     *
     * @param array<string, mixed> $value The object data to analyze
     *
     * @return array<string, mixed> Object schema with properties
     */
    private static function generateObjectSchemaFromArray(array $value): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        foreach ($value as $key => $propertyValue) {
            $schema['properties'][$key] = self::generateSchemaArray($propertyValue);
            $schema['required'][] = $key;
        }

        return $schema;
    }

    /**
     * Generate schema for object instance.
     *
     * Analyzes object properties and generates schema constraints.
     *
     * @param object $value The object to analyze
     *
     * @return array<string, mixed> Object schema
     */
    private static function generateObjectSchema(object $value): array
    {
        $properties = get_object_vars($value);

        return self::generateObjectSchemaFromArray($properties);
    }

    /**
     * Merge multiple schemas into a single schema.
     *
     * Finds common constraints across multiple schemas and combines them
     * using anyOf when types differ.
     *
     * @param array<array<string, mixed>> $schemas Schemas to merge
     *
     * @return array<string, mixed> Merged schema
     */
    private static function mergeSchemas(array $schemas): array
    {
        if (count($schemas) === 1) {
            return $schemas[0];
        }

        // Check if all schemas have the same type
        $types = array_unique(array_map(
            static fn (array $s): string => is_string($s['type'] ?? null) ? $s['type'] : 'mixed',
            $schemas,
        ));

        if (count($types) === 1) {
            // Same type - merge constraints
            return $schemas[0]; // Simplified - could be enhanced to merge constraints
        }

        // Different types - use anyOf
        return [
            'anyOf' => array_values(array_unique($schemas, SORT_REGULAR)),
        ];
    }
}
