<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Builders;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Enums\Format;
use Cline\JsonSchema\Enums\SchemaType;
use Cline\JsonSchema\ValueObjects\Schema;

use function array_map;

/**
 * Fluent builder for JSON Schema documents.
 *
 * Provides a convenient, chainable API for constructing JSON Schema documents
 * programmatically. Supports all common schema keywords and validation constraints.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/understanding-json-schema/reference/schema Understanding JSON Schema
 */
final class SchemaBuilder
{
    /**
     * The schema data being built.
     *
     * @var array<string, mixed>
     */
    private array $schema = [];

    /**
     * Create a new schema builder instance.
     *
     * Private constructor enforces use of the static create() method
     * for builder instantiation.
     */
    private function __construct() {}

    /**
     * Create a new schema builder.
     *
     * Static factory method to begin building a new JSON Schema document.
     *
     * @return self A new builder instance ready for method chaining
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the schema draft version.
     *
     * Specifies the JSON Schema specification version to use via the $schema keyword.
     * This determines which validation keywords and behaviors are available.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/schema#schema
     * @param  Draft $draft The draft version to use (e.g., Draft07, Draft202012)
     * @return self  Fluent interface for method chaining
     */
    public function draft(Draft $draft): self
    {
        $this->schema['$schema'] = $draft->value;

        return $this;
    }

    /**
     * Set the schema type.
     *
     * Specifies a single allowed type for values validated against this schema.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/type
     * @param  SchemaType $type The primitive type (e.g., String, Number, Object)
     * @return self       Fluent interface for method chaining
     */
    public function type(SchemaType $type): self
    {
        $this->schema['type'] = $type->value;

        return $this;
    }

    /**
     * Set multiple allowed types.
     *
     * Allows values to match any of the specified types. Useful for schemas
     * that accept multiple type variations (e.g., string or null).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/type
     * @param  array<SchemaType> $types Array of allowed types
     * @return self              Fluent interface for method chaining
     */
    public function types(array $types): self
    {
        $this->schema['type'] = array_map(static fn (SchemaType $type): string => $type->value, $types);

        return $this;
    }

    /**
     * Set the title.
     *
     * Provides a human-readable title for the schema, typically used in
     * documentation and UI generation.
     *
     * @param string $title The schema title
     *
     * @return self Fluent interface for method chaining
     */
    public function title(string $title): self
    {
        $this->schema['title'] = $title;

        return $this;
    }

    /**
     * Set the description.
     *
     * Provides a detailed description of the schema's purpose and constraints,
     * useful for documentation and developer guidance.
     *
     * @param string $description The schema description
     *
     * @return self Fluent interface for method chaining
     */
    public function description(string $description): self
    {
        $this->schema['description'] = $description;

        return $this;
    }

    /**
     * Set allowed enum values.
     *
     * Restricts values to exactly match one of the specified values.
     * Supports any JSON type including strings, numbers, objects, and arrays.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/enum
     * @param  array<mixed> $values Array of allowed values
     * @return self         Fluent interface for method chaining
     */
    public function enum(array $values): self
    {
        $this->schema['enum'] = $values;

        return $this;
    }

    /**
     * Set a constant value.
     *
     * Requires the value to exactly match the specified constant.
     * Similar to enum with a single value, but semantically different.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/const
     * @param  mixed $value The required constant value
     * @return self  Fluent interface for method chaining
     */
    public function const(mixed $value): self
    {
        $this->schema['const'] = $value;

        return $this;
    }

    /**
     * Set the minimum value for numbers.
     *
     * Specifies the inclusive minimum value for numeric types.
     * Values equal to this minimum are valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @param  float|int $minimum The minimum allowed value (inclusive)
     * @return self      Fluent interface for method chaining
     */
    public function minimum(float|int $minimum): self
    {
        $this->schema['minimum'] = $minimum;

        return $this;
    }

    /**
     * Set the maximum value for numbers.
     *
     * Specifies the inclusive maximum value for numeric types.
     * Values equal to this maximum are valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @param  float|int $maximum The maximum allowed value (inclusive)
     * @return self      Fluent interface for method chaining
     */
    public function maximum(float|int $maximum): self
    {
        $this->schema['maximum'] = $maximum;

        return $this;
    }

    /**
     * Set the exclusive minimum value for numbers.
     *
     * Specifies the exclusive minimum value for numeric types.
     * Values must be strictly greater than this value.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @param  float|int $exclusiveMinimum The minimum value (exclusive, not allowed)
     * @return self      Fluent interface for method chaining
     */
    public function exclusiveMinimum(float|int $exclusiveMinimum): self
    {
        $this->schema['exclusiveMinimum'] = $exclusiveMinimum;

        return $this;
    }

    /**
     * Set the exclusive maximum value for numbers.
     *
     * Specifies the exclusive maximum value for numeric types.
     * Values must be strictly less than this value.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @param  float|int $exclusiveMaximum The maximum value (exclusive, not allowed)
     * @return self      Fluent interface for method chaining
     */
    public function exclusiveMaximum(float|int $exclusiveMaximum): self
    {
        $this->schema['exclusiveMaximum'] = $exclusiveMaximum;

        return $this;
    }

    /**
     * Set the multipleOf constraint for numbers.
     *
     * Requires numeric values to be divisible by the specified value.
     * Useful for enforcing increments (e.g., multiples of 0.01 for currency).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#multiples
     * @param  float|int $multipleOf The divisor value
     * @return self      Fluent interface for method chaining
     */
    public function multipleOf(float|int $multipleOf): self
    {
        $this->schema['multipleOf'] = $multipleOf;

        return $this;
    }

    /**
     * Set the minimum string length.
     *
     * Specifies the minimum number of characters (not bytes) required
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length
     * for string values.
     *
     * @param int $minLength The minimum character count (inclusive)
     *
     * @return self Fluent interface for method chaining
     */
    public function minLength(int $minLength): self
    {
        $this->schema['minLength'] = $minLength;

        return $this;
    }

    /**
     * Set the maximum string length.
     *
     * Specifies the maximum number of characters (not bytes) allowed
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length
     * for string values.
     *
     * @param int $maxLength The maximum character count (inclusive)
     *
     * @return self Fluent interface for method chaining
     */
    public function maxLength(int $maxLength): self
    {
        $this->schema['maxLength'] = $maxLength;

        return $this;
    }

    /**
     * Set the pattern constraint for strings.
     *
     * Requires string values to match the specified regular expression.
     * Uses ECMA-262 regex syntax.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#regexp
     *
     * @param string $pattern The regular expression pattern (without delimiters)
     *
     * @return self Fluent interface for method chaining
     */
    public function pattern(string $pattern): self
    {
        $this->schema['pattern'] = $pattern;

        return $this;
    }

    /**
     * Set the format constraint for strings.
     *
     * Applies semantic validation for common string formats (e.g., email, uri, uuid).
     * Format validation is separate from type validation.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#format
     *
     * @param Format $format The semantic format to validate against
     *
     * @return self Fluent interface for method chaining
     */
    public function format(Format $format): self
    {
        $this->schema['format'] = $format->value;

        return $this;
    }

    /**
     * Set object properties.
     *
     * Defines the schema for each property in an object. Each property can be
     * specified as either a Schema instance or a raw schema array.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#properties
     * @param  array<string, array<string, mixed>|Schema> $properties Map of property names to their schemas
     * @return self                                       Fluent interface for method chaining
     */
    public function properties(array $properties): self
    {
        $this->schema['properties'] = [];

        foreach ($properties as $name => $property) {
            if ($property instanceof Schema) {
                $this->schema['properties'][$name] = $property->toArray();
            } else {
                $this->schema['properties'][$name] = $property;
            }
        }

        return $this;
    }

    /**
     * Set required properties.
     *
     * Specifies which object properties must be present for validation to pass.
     * Properties not in this list are optional.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#required
     * @param  array<string> $required Array of property names that must be present
     * @return self          Fluent interface for method chaining
     */
    public function required(array $required): self
    {
        $this->schema['required'] = $required;

        return $this;
    }

    /**
     * Set additional properties constraint.
     *
     * Controls whether properties not defined in the properties keyword are allowed.
     * Can be false (no additional properties), true (any additional properties),
     * or a schema (additional properties must match the schema).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#additionalproperties
     * @param  array<string, mixed>|bool $additionalProperties Schema for additional properties or boolean flag
     * @return self                      Fluent interface for method chaining
     */
    public function additionalProperties(array|bool $additionalProperties): self
    {
        $this->schema['additionalProperties'] = $additionalProperties;

        return $this;
    }

    /**
     * Set minimum number of properties.
     *
     * Specifies the minimum number of properties an object must have
     * to be valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size
     * @param  int  $minProperties The minimum property count (inclusive)
     * @return self Fluent interface for method chaining
     */
    public function minProperties(int $minProperties): self
    {
        $this->schema['minProperties'] = $minProperties;

        return $this;
    }

    /**
     * Set maximum number of properties.
     *
     * Specifies the maximum number of properties an object can have
     * to be valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size
     * @param  int  $maxProperties The maximum property count (inclusive)
     * @return self Fluent interface for method chaining
     */
    public function maxProperties(int $maxProperties): self
    {
        $this->schema['maxProperties'] = $maxProperties;

        return $this;
    }

    /**
     * Set array items schema.
     *
     * Defines the schema that all array elements must conform to.
     * Each item in the array will be validated against this schema.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#items
     * @param  array<string, mixed>|Schema $items The schema for array elements
     * @return self                        Fluent interface for method chaining
     */
    public function items(array|Schema $items): self
    {
        $this->schema['items'] = $items instanceof Schema ? $items->toArray() : $items;

        return $this;
    }

    /**
     * Set minimum array length.
     *
     * Specifies the minimum number of elements an array must contain
     * to be valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#length
     * @param  int  $minItems The minimum item count (inclusive)
     * @return self Fluent interface for method chaining
     */
    public function minItems(int $minItems): self
    {
        $this->schema['minItems'] = $minItems;

        return $this;
    }

    /**
     * Set maximum array length.
     *
     * Specifies the maximum number of elements an array can contain
     * to be valid.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#length
     * @param  int  $maxItems The maximum item count (inclusive)
     * @return self Fluent interface for method chaining
     */
    public function maxItems(int $maxItems): self
    {
        $this->schema['maxItems'] = $maxItems;

        return $this;
    }

    /**
     * Set unique items constraint.
     *
     * Requires all array elements to be unique when set to true.
     * Uniqueness is determined by deep equality comparison.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#uniqueItems
     * @param  bool $uniqueItems Whether all array items must be unique (defaults to true)
     * @return self Fluent interface for method chaining
     */
    public function uniqueItems(bool $uniqueItems = true): self
    {
        $this->schema['uniqueItems'] = $uniqueItems;

        return $this;
    }

    /**
     * Set a reference to another schema.
     *
     * Creates a reference to a schema defined elsewhere using JSON Pointer or URI.
     * Supports both internal references (e.g., '#/definitions/User') and
     * external references (e.g., 'http://example.com/schema.json').
     *
     * @see https://json-schema.org/understanding-json-schema/structuring#dollarref
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3
     * @param  string $ref The JSON Pointer or URI reference
     * @return self   Fluent interface for method chaining
     */
    public function ref(string $ref): self
    {
        $this->schema['$ref'] = $ref;

        return $this;
    }

    /**
     * Build the schema.
     *
     * @return Schema The immutable schema instance
     */
    public function build(): Schema
    {
        return new Schema($this->schema);
    }

    /**
     * Get the schema as an array without building.
     *
     * @return array<string, mixed> The schema data
     */
    public function toArray(): array
    {
        return $this->schema;
    }
}
