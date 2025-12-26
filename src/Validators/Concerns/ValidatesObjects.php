<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use Cline\JsonSchema\Support\JsonDecoder;

use function array_all;
use function array_diff;
use function array_filter;
use function array_is_list;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function preg_match;
use function sprintf;

/**
 * Object validation support for JSON Schema.
 *
 * Implements comprehensive validation for JSON objects including property existence,
 * property count constraints, property name patterns, dependencies, and evaluation
 * tracking. Handles both legacy (dependencies) and modern (dependentRequired,
 * dependentSchemas, unevaluatedProperties) validation keywords across different
 * JSON Schema drafts.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/object Understanding JSON Schema - Objects
 * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.4 Draft-04 - Object Validation
 * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.5 Draft-06 - Object Validation
 * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.5 Draft-07 - Object Validation
 * @see https://json-schema.org/draft/2019-09/json-schema-core#rfc.section.9.3.2 Draft 2019-09 - Object Applicator Keywords
 * @see https://json-schema.org/draft/2020-12/json-schema-core#name-validation-keywords-for-obj Draft 2020-12 - Object Validation
 */
trait ValidatesObjects
{
    /**
     * Validate the required keyword (mandatory properties).
     *
     * Ensures that all properties listed in the required array are present in the
     * object instance. This constraint only applies to JSON objects, not arrays.
     * Missing required properties cause validation to fail with an error message
     * listing the missing fields.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#required-properties Understanding JSON Schema - Required Properties
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-required Draft 2020-12 - required
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the required property list
     * @return bool                 True if all required properties are present, false otherwise
     */
    protected function validateRequired(mixed $data, array $schema): bool
    {
        if (!isset($schema['required']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $keys = array_keys($data);

        /** @var array<int, string> $required */
        $required = $schema['required'];
        $missing = array_diff($required, $keys);

        if ($missing !== []) {
            $missingFields = implode(', ', $missing);
            $this->addError('required', 'Missing required properties: '.$missingFields);

            return false;
        }

        return true;
    }

    /**
     * Validate the minProperties keyword (minimum property count).
     *
     * Ensures the object has at least the specified number of properties.
     * Only applies to JSON objects; arrays and other types pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size Understanding JSON Schema - Property Count
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-minproperties Draft 2020-12 - minProperties
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the minProperties constraint
     * @return bool                 True if property count meets minimum requirement, false otherwise
     */
    protected function validateMinProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['minProperties']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $propertyCount = $this->getPropertyCount($data);

        return $propertyCount >= $schema['minProperties'];
    }

    /**
     * Validate the maxProperties keyword (maximum property count).
     *
     * Ensures the object has no more than the specified number of properties.
     * Only applies to JSON objects; arrays and other types pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the maxProperties constraint
     * @return bool                 True if property count does not exceed maximum, false otherwise
     */
    protected function validateMaxProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['maxProperties']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $propertyCount = $this->getPropertyCount($data);

        return $propertyCount <= $schema['maxProperties'];
    }

    /**
     * Validate the properties keyword (named property schemas).
     *
     * Validates each object property against its corresponding schema defined in
     * the properties keyword. Marks evaluated properties for unevaluatedProperties
     * tracking. Only applies to JSON objects; arrays pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#properties Understanding JSON Schema - Properties
     * @see https://json-schema.org/draft/2020-12/json-schema-core#name-properties Draft 2020-12 - properties
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing property schemas
     * @return bool                 True if all present properties validate against their schemas, false otherwise
     */
    protected function validateProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['properties']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        /** @var array<string, mixed> $properties */
        $properties = $schema['properties'];

        foreach ($properties as $property => $propertySchema) {
            // Mark property as evaluated at current location
            $this->markPropertyEvaluated($this->currentPath, (string) $property);

            if (!isset($data[$property])) {
                continue;
            }

            // Update path for nested validation
            $oldPath = $this->currentPath;
            $this->currentPath .= '.'.$property;

            /** @var array<string, mixed>|bool $propertySchema */
            $valid = $this->validateSchema($data[$property], $propertySchema);

            // Restore path
            $this->currentPath = $oldPath;

            if (!$valid) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the additionalProperties keyword (catch-all property schema).
     *
     * Validates properties not covered by the properties or patternProperties keywords.
     * When set to false, disallows any additional properties. When set to a schema,
     * validates additional properties against that schema. Marks additional properties
     * as evaluated for unevaluatedProperties tracking. Only applies to JSON objects.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#additional-properties Understanding JSON Schema - Additional Properties
     * @see https://json-schema.org/draft/2020-12/json-schema-core#name-additionalproperties Draft 2020-12 - additionalProperties
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the additionalProperties constraint
     * @return bool                 True if additional properties are allowed and valid, false otherwise
     */
    protected function validateAdditionalProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['additionalProperties'])) {
            return true;
        }

        // Only applies to objects, not arrays
        if (!$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        // Get properties covered by 'properties' keyword
        /** @var array<string, mixed> $properties */
        $properties = $schema['properties'] ?? [];
        $definedProperties = array_keys($properties);

        // Check each data property
        foreach (array_keys($data) as $property) {
            // Skip if covered by 'properties'
            if (in_array($property, $definedProperties, true)) {
                continue;
            }

            // Skip if covered by 'patternProperties'
            if (isset($schema['patternProperties'])) {
                /** @var array<string, mixed> $patternProperties */
                $patternProperties = $schema['patternProperties'];
                $matchedPattern = false;

                foreach (array_keys($patternProperties) as $originalPattern) {
                    // Check if UTF-8 mode needed BEFORE translation
                    $needsUtf8 = $this->needsUtf8Mode((string) $originalPattern);

                    // Translate ECMA 262 Unicode property names to PCRE
                    $pattern = $this->translateUnicodeProperties((string) $originalPattern);

                    $modifier = $needsUtf8 ? 'u' : '';

                    if (preg_match(sprintf('/%s/%s', $pattern, $modifier), (string) $property)) {
                        $matchedPattern = true;

                        break;
                    }
                }

                if ($matchedPattern) {
                    continue;
                }
            }

            // This property is additional (not covered by properties or patternProperties)
            // Mark it as evaluated at current location
            $this->markPropertyEvaluated($this->currentPath, (string) $property);

            // If additionalProperties is false, fail validation
            if ($schema['additionalProperties'] === false) {
                return false;
            }

            // If additionalProperties is a schema, validate against it
            if (!is_array($schema['additionalProperties'])) {
                continue;
            }

            // Update path for nested validation
            $oldPath = $this->currentPath;
            $this->currentPath .= '.'.$property;

            /** @var array<string, mixed> $additionalPropertiesSchema */
            $additionalPropertiesSchema = $schema['additionalProperties'];
            $valid = $this->validateSchema($data[$property], $additionalPropertiesSchema);

            // Restore path
            $this->currentPath = $oldPath;

            if (!$valid) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate dependencies keyword (Draft-04 through Draft-07).
     *
     * The dependencies keyword validates conditional requirements between properties.
     * It supports two forms:
     * - Property dependencies: When a property is present, other properties must also be present
     * - Schema dependencies: When a property is present, the entire instance must validate against a schema
     *
     * Deprecated in Draft 2019-09 in favor of dependentRequired and dependentSchemas.
     *
     * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.5.7 Draft-07 dependencies
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#dependencies Understanding JSON Schema - Dependencies
     * @param  mixed                $data   The data to validate
     * @param  array<string, mixed> $schema The schema definition
     * @return bool                 True if valid
     */
    protected function validateDependencies(mixed $data, array $schema): bool
    {
        if (!isset($schema['dependencies']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        /** @var array<string, mixed> $dependencies */
        $dependencies = $schema['dependencies'];

        foreach ($dependencies as $property => $dependency) {
            // Dependency only applies if the property is present
            if (!isset($data[$property])) {
                continue;
            }

            // Distinguish between property dependency (array) and schema dependency (object/boolean)
            // Property dependency: sequential array like ["foo", "bar"]
            // Schema dependency: associative array like {"type": "integer"} or boolean (true/false)
            if (is_array($dependency) && array_is_list($dependency)) {
                // Property dependency (array of required properties)
                $keys = array_keys($data);

                /** @var array<int, string> $dependency */
                $missing = array_diff($dependency, $keys);

                if ($missing !== []) {
                    return false;
                }

                continue;
            }

            // Schema dependency (must validate entire instance against schema)
            // Can be an object schema or boolean schema (true/false)
            if (!is_array($dependency) && !is_bool($dependency)) {
                continue;
            }

            /** @var array<string, mixed>|bool $dependency */
            if (!$this->validateSchema($data, $dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate patternProperties keyword (Draft-05+).
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validatePatternProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['patternProperties']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        /** @var array<string, mixed> $patternProperties */
        $patternProperties = $schema['patternProperties'];

        foreach ($patternProperties as $originalPattern => $propertySchema) {
            foreach ($data as $property => $value) {
                // Check if UTF-8 mode needed BEFORE translation (ECMA classes need it)
                $needsUtf8 = $this->needsUtf8Mode((string) $originalPattern);

                // Translate ECMA 262 Unicode property names to PCRE
                $pattern = $this->translateUnicodeProperties((string) $originalPattern);

                // Use 'u' modifier for patterns that need UTF-8 support
                $modifier = $needsUtf8 ? 'u' : '';

                if (!preg_match(sprintf('/%s/%s', $pattern, $modifier), (string) $property)) {
                    continue;
                }

                // Mark property as evaluated at current location
                $this->markPropertyEvaluated($this->currentPath, (string) $property);

                // Update path for nested validation
                $oldPath = $this->currentPath;
                $this->currentPath .= '.'.$property;

                /** @var array<string, mixed>|bool $propertySchema */
                $valid = $this->validateSchema($value, $propertySchema);

                // Restore path
                $this->currentPath = $oldPath;

                if (!$valid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate propertyNames keyword (Draft-06+).
     *
     * All property names must match the schema.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validatePropertyNames(mixed $data, array $schema): bool
    {
        if (!isset($schema['propertyNames']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        // Skip validation for empty objects (they have no real properties)
        if (JsonDecoder::isEmptyObject($data)) {
            return true;
        }

        /** @var array<string, mixed>|bool $propertyNamesSchema */
        $propertyNamesSchema = $schema['propertyNames'];

        return array_all(array_keys($data), fn ($property) => $this->validateSchema($property, $propertyNamesSchema));
    }

    /**
     * Validate dependentRequired keyword (Draft 2019-09+).
     *
     * Replacement for property-based dependencies.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateDependentRequired(mixed $data, array $schema): bool
    {
        if (!isset($schema['dependentRequired']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        /** @var array<string, mixed> $dependentRequired */
        $dependentRequired = $schema['dependentRequired'];

        foreach ($dependentRequired as $property => $required) {
            if (!isset($data[$property])) {
                continue;
            }

            $keys = array_keys($data);

            /** @var array<int, string> $required */
            $missing = array_diff($required, $keys);

            if ($missing !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate dependentSchemas keyword (Draft 2019-09+).
     *
     * Replacement for schema-based dependencies.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateDependentSchemas(mixed $data, array $schema): bool
    {
        if (!isset($schema['dependentSchemas']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        /** @var array<string, mixed> $dependentSchemas */
        $dependentSchemas = $schema['dependentSchemas'];

        foreach ($dependentSchemas as $property => $dependentSchema) {
            if (!isset($data[$property])) {
                continue;
            }

            /** @var array<string, mixed>|bool $dependentSchema */
            if (!$this->validateSchema($data, $dependentSchema)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the unevaluatedProperties keyword (Draft 2019-09+).
     *
     * Validates properties that were not evaluated by properties, patternProperties,
     * additionalProperties, or any composition keywords (allOf, anyOf, oneOf, if/then/else).
     * When set to false, disallows unevaluated properties. When set to a schema,
     * validates unevaluated properties against that schema. This provides stricter
     * validation than additionalProperties by accounting for properties evaluated
     * anywhere in the schema, including nested composition structures.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#unevaluated-properties
     * @see https://json-schema.org/draft/2019-09/json-schema-core#rfc.section.9.3.2.4
     * @param  mixed                $data   The instance to validate (must be object for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the unevaluatedProperties constraint
     * @return bool                 True if all unevaluated properties are allowed and valid, false otherwise
     */
    protected function validateUnevaluatedProperties(mixed $data, array $schema): bool
    {
        if (!isset($schema['unevaluatedProperties']) || !$this->isJsonObject($data)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        // Get all evaluated properties from tracking
        $evaluated = $this->getEvaluatedProperties($this->currentPath);

        // Get actual property keys (excluding empty object marker)
        $actualKeys = array_keys($data);

        if (JsonDecoder::isEmptyObject($data)) {
            // Empty object has no real properties
            $actualKeys = [];
        } else {
            // Filter out the marker if it somehow got included
            $actualKeys = array_filter($actualKeys, fn (int|string $key): bool => $key !== '__EMPTY_JSON_OBJECT__');
        }

        // Get unevaluated properties
        $unevaluated = array_diff($actualKeys, $evaluated);

        // If unevaluatedProperties is true, mark all unevaluated properties as evaluated
        // This allows outer scopes to see them as evaluated
        if ($schema['unevaluatedProperties'] === true) {
            foreach ($unevaluated as $property) {
                $this->markPropertyEvaluated($this->currentPath, (string) $property);
            }

            return true;
        }

        // If unevaluatedProperties is false, no unevaluated properties allowed
        if ($schema['unevaluatedProperties'] === false) {
            return $unevaluated === [];
        }

        // Validate unevaluated properties against schema
        if (is_array($schema['unevaluatedProperties'])) {
            foreach ($unevaluated as $property) {
                // Mark as evaluated since we're validating it now
                $this->markPropertyEvaluated($this->currentPath, (string) $property);

                // Update path for nested validation
                $oldPath = $this->currentPath;
                $this->currentPath .= '.'.$property;

                /** @var array<string, mixed> $unevaluatedPropertiesSchema */
                $unevaluatedPropertiesSchema = $schema['unevaluatedProperties'];
                $valid = $this->validateSchema($data[$property], $unevaluatedPropertiesSchema);

                // Restore path
                $this->currentPath = $oldPath;

                if (!$valid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if data represents a JSON object rather than a JSON array.
     *
     * Distinguishes between JSON objects (associative arrays with string keys) and
     * JSON arrays (sequential arrays with integer keys starting at 0). Uses JsonDecoder
     * markers to correctly identify empty objects {} versus empty arrays [], which are
     * otherwise indistinguishable in PHP.
     *
     * @param mixed $data The data to check for object type
     *
     * @return bool True if the data represents a JSON object, false for arrays or other types
     */
    private function isJsonObject(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Check for empty object marker (from JsonDecoder)
        if (JsonDecoder::isEmptyObject($data)) {
            return true;
        }

        // Empty arrays (without marker) are JSON arrays
        if ($data === []) {
            return false;
        }

        // Non-empty associative arrays are objects
        return !array_is_list($data);
    }

    /**
     * Calculate the number of properties in an object.
     *
     * Counts actual object properties while correctly handling empty object markers
     * added by JsonDecoder. Empty objects ({}) return 0, not 1, even though they
     * contain a marker key internally.
     *
     * @param array<string, mixed> $data The object data to count properties for
     *
     * @return int The number of actual properties (excluding internal markers)
     */
    private function getPropertyCount(array $data): int
    {
        // If it's an empty object marker, it has 0 properties
        if (JsonDecoder::isEmptyObject($data)) {
            return 0;
        }

        // Otherwise count all keys (normal property counting)
        return count($data);
    }
}
