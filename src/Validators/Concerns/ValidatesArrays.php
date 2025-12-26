<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use function array_diff;
use function array_is_list;
use function array_keys;
use function assert;
use function count;
use function is_array;
use function sprintf;

/**
 * Array validation logic for JSON Schema.
 *
 * Implements validation for array-specific keywords including items, prefixItems,
 * additionalItems, minItems, maxItems, uniqueItems, contains, and unevaluatedItems.
 *
 * Handles both modern (Draft 2020-12) and legacy array validation patterns:
 * - Modern: prefixItems + items for tuple and additional item validation
 * - Legacy: items as array (tuple) + additionalItems
 *
 * Tracks evaluated items for unevaluatedItems implementation per the specification.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/understanding-json-schema/reference/array Array validation guide
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.3.1.2 items keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.3.1.1 prefixItems keyword
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-6.4 Array validation keywords
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-11.2 unevaluatedItems keyword
 */
trait ValidatesArrays
{
    /**
     * Validate items keyword.
     *
     * In Draft 2020-12, items applies to all array items or items beyond prefixItems.
     * In earlier drafts, items can be an array (tuple validation) or a schema for all items.
     *
     * Marks validated items as evaluated for unevaluatedItems tracking.
     *
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.3.1.2 items keyword specification
     * @see https://json-schema.org/understanding-json-schema/reference/array#items Array items validation
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid, false otherwise
     */
    protected function validateItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['items']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));
        $items = $schema['items'];

        // In Draft 2020-12, if prefixItems is present, items only applies to items
        // beyond the prefixItems positions
        $startIndex = 0;

        if (isset($schema['prefixItems']) && is_array($schema['prefixItems'])) {
            $startIndex = count($schema['prefixItems']);
        }

        // Check if items is a tuple schema (array of schemas) - legacy Draft 04-07 behavior
        if (is_array($items) && array_is_list($items)) {
            // Tuple validation: each position has its own schema
            foreach ($items as $index => $itemSchema) {
                if (!isset($data[$index])) {
                    // Optional items - no more data to validate
                    break;
                }

                // Mark item as evaluated at current location
                $this->markItemEvaluated($this->currentPath, $index);

                // Update path for nested validation
                $oldPath = $this->currentPath;
                $this->currentPath .= '['.$index.']';

                /** @var array<string, mixed>|bool $subSchema */
                $subSchema = $itemSchema;
                $valid = $this->validateSchema($data[$index], $subSchema);

                // Restore path
                $this->currentPath = $oldPath;

                if (!$valid) {
                    return false;
                }
            }

            return true;
        }

        // Single schema for all items (or false to disallow additional items)
        foreach ($data as $index => $item) {
            // Skip items covered by prefixItems
            if ($index < $startIndex) {
                continue;
            }

            // Mark item as evaluated at current location
            $this->markItemEvaluated($this->currentPath, (int) $index);

            // Update path for nested validation
            $oldPath = $this->currentPath;
            $this->currentPath .= '['.$index.']';

            /** @var array<string, mixed>|bool $subSchema */
            $subSchema = $items;
            $valid = $this->validateSchema($item, $subSchema);

            // Restore path
            $this->currentPath = $oldPath;

            if (!$valid) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate minItems keyword.
     *
     * Ensures the array has at least the specified number of items.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#length
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if array length >= minItems, false otherwise
     */
    protected function validateMinItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['minItems']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        return count($data) >= $schema['minItems'];
    }

    /**
     * Validate maxItems keyword.
     *
     * Ensures the array has at most the specified number of items.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#length Array length constraints
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-6.4.2 maxItems specification
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if array length <= maxItems, false otherwise
     */
    protected function validateMaxItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['maxItems']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        return count($data) <= $schema['maxItems'];
    }

    /**
     * Validate uniqueItems keyword.
     *
     * Ensures all array items are unique using JSON Schema equality semantics
     * (numeric type coercion, object key order independence).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#uniqueness
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if all items unique or uniqueItems false, false if duplicates found
     */
    protected function validateUniqueItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['uniqueItems']) || !$schema['uniqueItems'] || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        // Use JSON equality comparison (which handles object key order independence)
        $count = count($data);

        for ($i = 0; $i < $count; ++$i) {
            for ($j = $i + 1; $j < $count; ++$j) {
                if ($this->jsonEquals($data[$i], $data[$j])) {
                    return false; // Found duplicate
                }
            }
        }

        return true; // All items are unique
    }

    /**
     * Validate additionalItems keyword (Draft 04-2019-09).
     *
     * For tuple validation - validates items beyond those specified in items array.
     * Replaced by the items keyword in Draft 2020-12.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#additionalItems Additional items
     * @see https://json-schema.org/draft/2019-09/json-schema-core#rfc.section.9.3.1.2 additionalItems specification
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid, false otherwise
     */
    protected function validateAdditionalItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['additionalItems']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        // additionalItems only applies when items is an array (tuple validation)
        if (!isset($schema['items']) || !is_array($schema['items'])) {
            return true;
        }

        // Check if items is a tuple schema (array of schemas)
        $isTuple = isset($schema['items'][0]) && is_array($schema['items'][0]);

        if (!$isTuple) {
            return true;
        }

        $tupleLength = count($schema['items']);

        // If additionalItems is false, no additional items allowed
        if ($schema['additionalItems'] === false) {
            return count($data) <= $tupleLength;
        }

        // If additionalItems is a schema, validate items beyond tuple length
        if (is_array($schema['additionalItems'])) {
            /** @var array<string, mixed> $additionalItemsSchema */
            $additionalItemsSchema = $schema['additionalItems'];
            $counter = count($data);

            for ($i = $tupleLength; $i < $counter; ++$i) {
                // Mark item as evaluated
                $this->markItemEvaluated($this->currentPath, $i);

                if (!$this->validateSchema($data[$i], $additionalItemsSchema)) {
                    return false;
                }
            }
        } else {
            $counter = count($data);

            // additionalItems is true or any other truthy value - mark items as evaluated
            for ($i = $tupleLength; $i < $counter; ++$i) {
                $this->markItemEvaluated($this->currentPath, $i);
            }
        }

        return true;
    }

    /**
     * Validate contains keyword (Draft 06+).
     *
     * Ensures the array contains at least minContains (default 1) and at most
     * maxContains items matching the contains schema. Matching items are marked
     * as evaluated for unevaluatedItems tracking.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#contains
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if contains constraint satisfied, false otherwise
     */
    protected function validateContains(mixed $data, array $schema): bool
    {
        if (!isset($schema['contains']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        // Count how many items match the contains schema
        $matchCount = 0;

        /** @var array<string, mixed>|bool $subSchema */
        $subSchema = $schema['contains'];

        // Save error state - we'll only keep errors if no items match
        $errorsBeforeContains = $this->errors;

        foreach ($data as $index => $item) {
            // Clear errors before checking this item
            $this->errors = $errorsBeforeContains;

            if (!$this->validateSchema($item, $subSchema)) {
                continue;
            }

            // Mark matching item as evaluated
            $this->markItemEvaluated($this->currentPath, (int) $index);
            ++$matchCount;
        }

        // Restore original errors (contains check shouldn't add errors from individual item failures)
        $this->errors = $errorsBeforeContains;

        // Check minContains (default 1 if not specified, per Draft 2019-09+)
        $minContains = $schema['minContains'] ?? 1;

        if ($matchCount < $minContains) {
            $this->addError('contains', sprintf('Array must contain at least %d matching item(s)', $minContains));

            return false;
        }

        // Check maxContains (unlimited if not specified)
        if (isset($schema['maxContains']) && $matchCount > $schema['maxContains']) {
            $this->addError('contains', sprintf('Array must contain at most %d matching item(s)', $schema['maxContains']));

            return false;
        }

        return true;
    }

    /**
     * Validate unevaluatedItems keyword (Draft 2019-09+).
     *
     * Items not covered by items, additionalItems, or contains must validate
     * against this schema.
     *
     * Note: This is a simplified implementation that works for basic cases.
     * Full spec compliance requires tracking all evaluated items across
     * the entire schema including composition keywords.
     *
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-11.2 unevaluatedItems specification
     * @see https://json-schema.org/understanding-json-schema/reference/array#unevaluatedItems Unevaluated items
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid, false otherwise
     */
    protected function validateUnevaluatedItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['unevaluatedItems']) || !$this->isJsonArray($data)) {
            return true;
        }

        assert(is_array($data));

        // Get all evaluated items from tracking
        $evaluated = $this->getEvaluatedItems($this->currentPath);

        // Get unevaluated item indices
        $allIndices = array_keys($data);
        $unevaluated = array_diff($allIndices, $evaluated);

        // If unevaluatedItems is true, mark all unevaluated items as evaluated
        // This allows outer scopes to see them as evaluated
        if ($schema['unevaluatedItems'] === true) {
            foreach ($unevaluated as $index) {
                $this->markItemEvaluated($this->currentPath, (int) $index);
            }

            return true;
        }

        // If unevaluatedItems is false, no unevaluated items allowed
        if ($schema['unevaluatedItems'] === false) {
            return $unevaluated === [];
        }

        // Validate unevaluated items against schema
        if (is_array($schema['unevaluatedItems'])) {
            /** @var array<string, mixed> $unevaluatedItemsSchema */
            $unevaluatedItemsSchema = $schema['unevaluatedItems'];

            foreach ($unevaluated as $index) {
                // Mark as evaluated since we're validating it now
                $this->markItemEvaluated($this->currentPath, (int) $index);

                // Update path for nested validation
                $oldPath = $this->currentPath;
                $this->currentPath .= '['.$index.']';

                $valid = $this->validateSchema($data[$index], $unevaluatedItemsSchema);

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
     * Validate prefixItems keyword (Draft 2020-12+).
     *
     * Validates array items against positional schemas for tuple validation.
     * This keyword replaces the items-as-array pattern from earlier drafts.
     * Each item at a given index is validated against the corresponding schema
     * in the prefixItems array.
     *
     * Items are marked as evaluated for unevaluatedItems tracking.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#tupleValidation
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if all prefix items valid, false otherwise
     */
    protected function validatePrefixItems(mixed $data, array $schema): bool
    {
        if (!isset($schema['prefixItems']) || !$this->isJsonArray($data) || !is_array($schema['prefixItems'])) {
            return true;
        }

        assert(is_array($data));

        foreach ($schema['prefixItems'] as $index => $itemSchema) {
            if (!isset($data[$index])) {
                continue;
            }

            // Mark item as evaluated
            $this->markItemEvaluated($this->currentPath, (int) $index);

            // Update path for nested validation
            $oldPath = $this->currentPath;
            $this->currentPath .= '['.$index.']';

            /** @var array<string, mixed>|bool $subSchema */
            $subSchema = $itemSchema;
            $valid = $this->validateSchema($data[$index], $subSchema);

            // Restore path
            $this->currentPath = $oldPath;

            if (!$valid) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data is a JSON array (list), not a JSON object.
     *
     * @param mixed $data The data to check
     *
     * @return bool True if it's an array/list
     */
    private function isJsonArray(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Empty arrays are ambiguous, but for array keywords they should match
        if ($data === []) {
            return true;
        }

        return array_is_list($data);
    }
}
