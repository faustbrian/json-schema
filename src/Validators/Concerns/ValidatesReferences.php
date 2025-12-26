<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use function array_count_values;
use function array_key_exists;
use function array_pop;
use function assert;
use function count;
use function end;
use function explode;
use function is_array;
use function is_bool;
use function is_string;
use function mb_rtrim;
use function mb_substr;
use function rawurldecode;
use function str_contains;
use function str_replace;
use function str_starts_with;

/**
 * Schema reference resolution and validation support for JSON Schema.
 *
 * Implements comprehensive $ref resolution including JSON Pointers, anchors, and
 * external schema loading. Supports legacy $recursiveRef (Draft 2019-09) and modern
 * $dynamicRef (Draft 2020-12+) for dynamic scope resolution. Handles recursion
 * detection to prevent infinite loops during validation of recursive schemas.
 *
 * Dependencies: Requires rootSchema, dynamicScope, schemaRegistry properties and
 * validateSchema, getCurrentBaseUri, resolveUri methods from the using class.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/structuring Understanding JSON Schema - Structuring
 * @see https://datatracker.ietf.org/doc/html/rfc6901 RFC 6901 - JSON Pointer
 * @see https://json-schema.org/draft-04/json-schema-core#rfc.section.7 Draft-04 - Schema References
 * @see https://json-schema.org/draft-06/json-schema-core#rfc.section.8 Draft-06 - Schema References
 * @see https://json-schema.org/draft-07/json-schema-core#rfc.section.8 Draft-07 - Schema References
 * @see https://json-schema.org/draft/2019-09/json-schema-core#rfc.section.8.2 Draft 2019-09 - Schema References and Recursion
 * @see https://json-schema.org/draft/2020-12/json-schema-core#name-schema-references Draft 2020-12 - Schema References
 */
trait ValidatesReferences
{
    /**
     * Validate the $ref keyword (schema reference).
     *
     * Resolves and validates the instance against a referenced schema. Supports
     * multiple reference types: JSON Pointers (#/path/to/schema), anchors (#anchor),
     * id-based references, and external schema URIs. Includes recursion detection
     * with different thresholds for metaschemas versus regular schemas to prevent
     * infinite validation loops while allowing legitimate recursive data structures.
     *
     * @see https://json-schema.org/understanding-json-schema/structuring#ref
     * @see https://json-schema.org/draft/2020-12/json-schema-core#name-direct-references-with-ref
     * @param  mixed                $data   The instance to validate against the referenced schema
     * @param  array<string, mixed> $schema The schema definition containing the $ref keyword
     * @return bool                 True if the instance validates against the referenced schema, false otherwise
     */
    protected function validateRef(mixed $data, array $schema): bool
    {
        if (!isset($schema['$ref'])) {
            return true;
        }

        $reference = $schema['$ref'];
        assert(is_string($reference));

        // Resolve the reference against the current base URI
        $currentBase = $this->getCurrentBaseUri();
        $absoluteRef = $this->resolveUri($currentBase, $reference);

        // Early recursion detection to prevent infinite loops
        // Allow up to 2 occurrences of the same schema URI in the validation stack
        // This handles recursive data (tree->node->tree) while preventing metaschema loops
        $uriCounts = array_count_values($this->validatingSchemas);
        $currentCount = $uriCounts[$absoluteRef] ?? 0;

        if ($currentCount >= 2) {
            return true;
        }

        // First, check if the full URI (with fragment) is in the registry (for anchors)
        if (isset($this->schemaRegistry[$absoluteRef])) {
            // Smart recursion detection: count how many times this URI appears in the validation stack
            // Use different thresholds for metaschemas vs regular schemas
            $threshold = str_contains((string) $absoluteRef, 'json-schema.org') ? 200 : 50;

            $uriCounts = array_count_values($this->validatingSchemas);
            $currentCount = $uriCounts[$absoluteRef] ?? 0;

            if ($currentCount >= $threshold) {
                // Schema URI appears too many times in stack - infinite loop detected
                // Return true to break the cycle
                return true;
            }

            $this->validatingSchemas[] = $absoluteRef;

            /** @var array<string, mixed>|bool $registrySchema */
            $registrySchema = $this->schemaRegistry[$absoluteRef];

            // Check if we need to switch to a different draft validator
            $validator = $this->createValidatorForSchema($registrySchema);

            if ($validator !== null && is_array($registrySchema)) {
                // Use the appropriate draft validator
                $result = $validator->validate($data, $registrySchema);
                array_pop($this->validatingSchemas);

                return $result->valid;
            }

            $result = $this->validateSchema($data, $registrySchema);
            array_pop($this->validatingSchemas);

            return $result;
        }

        // Extract base URL and fragment if present
        $fragment = null;

        if (str_contains((string) $absoluteRef, '#')) {
            [$baseUrl, $fragment] = explode('#', (string) $absoluteRef, 2);
        } else {
            $baseUrl = $absoluteRef;
        }

        // Try to find the base schema in the registry
        if (isset($this->schemaRegistry[$baseUrl])) {
            $resolvedSchema = $this->schemaRegistry[$baseUrl];

            // If there's a fragment, use validateSchemaWithPointerContext to track base URIs
            if ($fragment !== null && $fragment !== '') {
                // Push the base schema's URI onto the stack so refs within it resolve correctly
                // This is safe because validateSchemaWithPointerContext doesn't call validateSchema on the base
                $this->baseUriStack[] = $baseUrl;
                $result = $this->validateSchemaWithPointerContext($data, $resolvedSchema, '#'.$fragment);
                array_pop($this->baseUriStack);

                return $result;
            }

            // No fragment, validate directly
            // Smart recursion detection
            $threshold = str_contains((string) $absoluteRef, 'json-schema.org') ? 200 : 50;
            $uriCounts = array_count_values($this->validatingSchemas);
            $currentCount = $uriCounts[$absoluteRef] ?? 0;

            if ($currentCount >= $threshold) {
                return true;
            }

            $this->validatingSchemas[] = $absoluteRef;

            /** @var array<string, mixed>|bool $resolvedSchema */
            // Check if we need to switch to a different draft validator
            $validator = $this->createValidatorForSchema($resolvedSchema);

            if ($validator !== null && is_array($resolvedSchema)) {
                // Use the appropriate draft validator
                $result = $validator->validate($data, $resolvedSchema);
                array_pop($this->validatingSchemas);

                return $result->valid;
            }

            $result = $this->validateSchema($data, $resolvedSchema);
            array_pop($this->validatingSchemas);

            return $result;
        }

        // Handle JSON Pointer references (starting with #/ or just #)
        if (str_starts_with($reference, '#/') || $reference === '#') {
            // First try to resolve against a schema in the registry matching the current base URI
            $currentBase = $this->getCurrentBaseUri();

            if ($currentBase !== '' && isset($this->schemaRegistry[$currentBase])) {
                $resolvedSchema = $this->resolvePointerInSchema($this->schemaRegistry[$currentBase], $reference);

                if ($resolvedSchema !== null) {
                    return $this->validateSchemaWithPointerContext($data, $this->schemaRegistry[$currentBase], $reference);
                }
            }

            // Fall back to resolving against root schema
            $resolvedSchema = $this->resolveReference($reference);

            if ($resolvedSchema === null) {
                return false;
            }

            return $this->validateSchemaWithPointerContext($data, $this->rootSchema, $reference);
        }

        // Handle plain anchor references (starting with # but not #/)
        // These are location-independent identifiers like "#foo"
        if (str_starts_with($reference, '#')) {
            // Resolve against current base URI to get the absolute anchor reference
            $currentBase = $this->getCurrentBaseUri();
            $anchorRef = $this->resolveUri($currentBase, $reference);

            // Look up in schema registry
            if (isset($this->schemaRegistry[$anchorRef])) {
                /** @var array<string, mixed>|bool $anchorSchema */
                $anchorSchema = $this->schemaRegistry[$anchorRef];

                return $this->validateSchema($data, $anchorSchema);
            }

            return false;
        }

        // Try to load external schema
        // First load the base schema (without fragment) if it's not in the registry
        if (!isset($this->schemaRegistry[$baseUrl])) {
            $externalSchema = $this->schemaLoader->load($baseUrl);

            if ($externalSchema === null) {
                // External schema could not be loaded, fail validation
                return false;
            }

            // Register the loaded schema and all its nested schemas
            $this->schemaRegistry[$baseUrl] = $externalSchema;
            $this->registerSchemas($externalSchema, $baseUrl);
        }

        // Now get the schema from registry (it should be there now)
        $resolvedSchema = $this->schemaRegistry[$baseUrl];

        // If there's a fragment, resolve it within the loaded schema
        if ($fragment !== null && $fragment !== '') {
            // Check if it's a JSON Pointer (starts with /) or an anchor (no /)
            if (str_starts_with($fragment, '/')) {
                // JSON Pointer - use pointer resolution
                $resolvedSchema = $this->resolvePointerInSchema($resolvedSchema, '#'.$fragment);
            } else {
                // Anchor - look up in registry
                $anchorRef = $baseUrl.'#'.$fragment;

                $resolvedSchema = $this->schemaRegistry[$anchorRef] ?? null;
            }
        }

        if ($resolvedSchema === null) {
            return false;
        }

        // Smart recursion detection before validating external schema
        $threshold = str_contains((string) $absoluteRef, 'json-schema.org') ? 200 : 50;
        $uriCounts = array_count_values($this->validatingSchemas);
        $currentCount = $uriCounts[$absoluteRef] ?? 0;

        if ($currentCount >= $threshold) {
            // Infinite loop detected, break cycle
            return true;
        }

        // Push the loaded schema's base URI onto the stack so that internal refs resolve correctly
        $this->baseUriStack[] = $baseUrl;

        // Push onto validation stack for recursion detection
        $this->validatingSchemas[] = $absoluteRef;

        // Validate data against resolved schema
        /** @var array<string, mixed>|bool $resolvedSchema */
        // Check if we need to switch to a different draft validator
        $validator = $this->createValidatorForSchema($resolvedSchema);

        if ($validator !== null && is_array($resolvedSchema)) {
            // Use the appropriate draft validator
            $result = $validator->validate($data, $resolvedSchema);
            array_pop($this->validatingSchemas);
            array_pop($this->baseUriStack);

            return $result->valid;
        }

        $result = $this->validateSchema($data, $resolvedSchema);

        // Pop from validation stack
        array_pop($this->validatingSchemas);

        // Pop the base URI
        array_pop($this->baseUriStack);

        return $result;
    }

    /**
     * Resolve a JSON Pointer reference to a schema fragment.
     *
     * Traverses the root schema using RFC 6901 JSON Pointer syntax to locate
     * the referenced subschema. Handles special encoding for tilde (~0) and
     * slash (~1) characters, plus URL decoding for fragments in URIs.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6901 RFC 6901 - JSON Pointer
     * @see https://json-schema.org/understanding-json-schema/structuring#json-pointer Understanding JSON Schema - JSON Pointer
     * @param  string $reference The JSON Pointer reference starting with # (e.g., #/definitions/foo)
     * @return mixed  The resolved schema (array, boolean, or any JSON value), or null if not found
     */
    protected function resolveReference(string $reference): mixed
    {
        // Handle root reference
        if ($reference === '#') {
            return $this->rootSchema;
        }

        // Remove the #/ prefix
        $pointer = mb_substr($reference, 2);

        if ($pointer === '') {
            // Reference to root schema
            return $this->rootSchema;
        }

        // Split the pointer into segments
        $segments = explode('/', $pointer);

        // Traverse the schema following the pointer
        $current = $this->rootSchema;

        foreach ($segments as $segment) {
            // URL-decode the segment first (for fragments in URIs)
            $segment = rawurldecode($segment);

            // Then decode JSON Pointer special characters (~0 and ~1)
            $segment = str_replace('~1', '/', $segment);
            $segment = str_replace('~0', '~', $segment);

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        // Current can be an array, boolean (true/false), or any other JSON value
        return $current;
    }

    /**
     * Resolve a JSON Pointer within a specific schema.
     *
     * Similar to resolveReference but works on any schema, not just rootSchema.
     *
     * @param array<string, mixed>|bool $schema    The schema to resolve within
     * @param string                    $reference The reference to resolve (JSON Pointer with #)
     *
     * @return mixed The resolved schema (array, true, false) or null if not found
     */
    protected function resolvePointerInSchema(array|bool $schema, string $reference): mixed
    {
        // Handle root reference
        if ($reference === '#') {
            return $schema;
        }

        // Boolean schemas don't have pointer segments
        if (is_bool($schema)) {
            return null;
        }

        // Must start with #/
        if (!str_starts_with($reference, '#/')) {
            return null;
        }

        // Remove the #/ prefix
        $pointer = mb_substr($reference, 2);

        if ($pointer === '') {
            // Reference to root of this schema
            return $schema;
        }

        // Split the pointer into segments
        $segments = explode('/', $pointer);

        // Traverse the schema following the pointer
        $current = $schema;

        foreach ($segments as $segment) {
            // URL-decode the segment first (for fragments in URIs)
            $segment = rawurldecode($segment);

            // Then decode JSON Pointer special characters (~0 and ~1)
            $segment = str_replace('~1', '/', $segment);
            $segment = str_replace('~0', '~', $segment);

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        // Current can be an array, boolean, or any other JSON value
        return $current;
    }

    /**
     * Validate schema with pointer context, updating base URI as we traverse.
     *
     * This ensures that intermediate schemas with id keywords update the base URI correctly.
     *
     * @param mixed                $data    The data to validate
     * @param array<string, mixed> $schema  The base schema to resolve within
     * @param string               $pointer The JSON Pointer (with #)
     *
     * @return bool True if valid
     */
    protected function validateSchemaWithPointerContext(mixed $data, array $schema, string $pointer): bool
    {
        // Handle root reference
        if ($pointer === '#') {
            /** @var array<string, mixed> $schema */
            return $this->validateSchema($data, $schema);
        }

        // Must start with #/
        if (!str_starts_with($pointer, '#/')) {
            /** @var array<string, mixed> $schema */
            return $this->validateSchema($data, $schema);
        }

        // Remove the #/ prefix
        $path = mb_substr($pointer, 2);

        if ($path === '') {
            /** @var array<string, mixed> $schema */
            return $this->validateSchema($data, $schema);
        }

        // Split the pointer into segments
        $segments = explode('/', $path);
        $segmentCount = count($segments);

        // Traverse the schema following the pointer, updating base URI for intermediate schemas with id
        $current = $schema;

        foreach ($segments as $index => $segment) {
            // URL-decode the segment
            $segment = rawurldecode($segment);

            // Decode JSON Pointer special characters
            $segment = str_replace('~1', '/', $segment);
            $segment = str_replace('~0', '~', $segment);

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];

            // Only push base URI for INTERMEDIATE schemas (not the final one)
            // The final schema's id will be handled by validateSchema
            $isLastSegment = $index === $segmentCount - 1;

            if ($isLastSegment) {
                continue;
            }

            if (!is_array($current)) {
                continue;
            }

            if (!isset($current['id']) && !isset($current['$id'])) {
                continue;
            }

            $id = $current['id'] ?? $current['$id'];

            if (!is_string($id)) {
                continue;
            }

            if ($id === '') {
                continue;
            }

            $currentBase = end($this->baseUriStack) ?: '';
            $newBase = $this->resolveUri($currentBase, $id);
            $this->baseUriStack[] = $newBase;
        }

        // Current can be an array, boolean (true/false), or any other JSON value
        // Boolean schemas are valid - true accepts everything, false rejects everything
        // Validate against the final schema (base URI stack has been updated along the way)
        /** @var array<string, mixed>|bool $current */
        $result = $this->validateSchema($data, $current);

        // Pop all base URIs we pushed (count INTERMEDIATE segments that had ids)
        $pushedCount = 0;
        $tempCurrent = $schema;
        $totalSegments = count($segments);

        foreach ($segments as $index => $segment) {
            $segment = rawurldecode($segment);
            $segment = str_replace('~1', '/', $segment);
            $segment = str_replace('~0', '~', $segment);

            if (!is_array($tempCurrent) || !array_key_exists($segment, $tempCurrent)) {
                break;
            }

            $tempCurrent = $tempCurrent[$segment];

            // Only count intermediate schemas (not the final one)
            $isLastSegment = $index === $totalSegments - 1;

            if ($isLastSegment) {
                continue;
            }

            if (!is_array($tempCurrent)) {
                continue;
            }

            if (!isset($tempCurrent['id']) && !isset($tempCurrent['$id'])) {
                continue;
            }

            ++$pushedCount;
        }

        // Pop the base URIs we pushed
        for ($i = 0; $i < $pushedCount; ++$i) {
            array_pop($this->baseUriStack);
        }

        return $result;
    }

    /**
     * Validate $recursiveRef keyword (Draft 2019-09).
     *
     * Resolves to the nearest schema with $recursiveAnchor: true in the dynamic scope.
     * If no $recursiveAnchor is found, behaves like $ref to the current schema resource root.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateRecursiveRef(mixed $data, array $schema): bool
    {
        if (!isset($schema['$recursiveRef'])) {
            return true;
        }

        $reference = $schema['$recursiveRef'];
        assert(is_string($reference));

        // $recursiveRef typically references "#" (root)
        if ($reference === '#') {
            // Find the current schema resource root (innermost schema with $id)
            $resourceRootIndex = null;

            for ($i = count($this->dynamicScope) - 1; $i >= 0; --$i) {
                $scopeEntry = $this->dynamicScope[$i];

                if (is_array($scopeEntry['schema'])
                    && (isset($scopeEntry['schema']['$id']) || isset($scopeEntry['schema']['id']))) {
                    $resourceRootIndex = $i;

                    break;
                }
            }

            // Get the resource root schema
            $resourceRoot = $resourceRootIndex !== null
                ? $this->dynamicScope[$resourceRootIndex]['schema']
                : $this->rootSchema;

            // Check if the current resource root has $recursiveAnchor: true
            // If not, just use the resource root without searching outward
            if (!is_array($resourceRoot)
                || !isset($resourceRoot['$recursiveAnchor'])
                || $resourceRoot['$recursiveAnchor'] !== true) {
                // Resource root doesn't have $recursiveAnchor: true, use it directly
                /** @var array<string, mixed>|bool $resourceRoot */
                return $this->validateSchema($data, $resourceRoot);
            }

            // Resource root has $recursiveAnchor: true
            // Search from OUTERMOST (oldest) toward current for the outermost $recursiveAnchor: true
            $foundAnchor = null;
            $startIndex = $resourceRootIndex ?? (count($this->dynamicScope) - 1);

            for ($i = 0; $i <= $startIndex; ++$i) {
                $scopeEntry = $this->dynamicScope[$i];

                // Check if this schema has $recursiveAnchor: true
                if (is_array($scopeEntry['schema'])
                    && isset($scopeEntry['schema']['$recursiveAnchor'])
                    && $scopeEntry['schema']['$recursiveAnchor'] === true) {
                    // Found a candidate - remember the FIRST (outermost) one
                    if ($foundAnchor === null) {
                        $foundAnchor = $scopeEntry['schema'];
                    }
                } elseif ($i > 0
                         && is_array($scopeEntry['schema'])
                         && (isset($scopeEntry['schema']['$id']) || isset($scopeEntry['schema']['id']))) {
                    // Hit a resource boundary ($id) without $recursiveAnchor: true
                    // This blocks further outward search
                    break;
                }
            }

            // Use the found anchor (or fall back to resource root if none found)
            $targetSchema = $foundAnchor ?? $resourceRoot;

            /** @var array<string, mixed> $targetSchema */
            return $this->validateSchema($data, $targetSchema);
        }

        // For non-root references, fall back to normal $ref resolution
        // Temporarily set $ref and use validateRef logic
        $refSchema = ['$ref' => $reference];

        return $this->validateRef($data, $refSchema);
    }

    /**
     * Validate the $dynamicRef keyword (Draft 2020-12+ dynamic scope resolution).
     *
     * Implements dynamic reference resolution that can change based on the validation
     * call stack. First resolves statically to find the initial target. If that target
     * has a matching $dynamicAnchor, searches the dynamic scope for the outermost
     * schema with the same anchor. This enables extending recursive schemas without
     * modifying the base schema.
     *
     * Resolution algorithm:
     * 1. Resolve the reference statically to find the initial target schema
     * 2. Check if the initial target has a matching $dynamicAnchor
     * 3. If yes, search dynamic scope for the FIRST (outermost) matching anchor
     * 4. If no dynamic match or no $dynamicAnchor, use static resolution
     *
     * @see https://json-schema.org/understanding-json-schema/structuring#dynamic-references
     * @see https://json-schema.org/draft/2020-12/json-schema-core#name-dynamic-references-with-dy
     * @param  mixed                $data   The instance to validate against the dynamically resolved schema
     * @param  array<string, mixed> $schema The schema definition containing the $dynamicRef keyword
     * @return bool                 True if the instance validates against the resolved schema, false otherwise
     */
    protected function validateDynamicRef(mixed $data, array $schema): bool
    {
        if (!isset($schema['$dynamicRef'])) {
            return true;
        }

        $reference = $schema['$dynamicRef'];

        // If reference contains a JSON Pointer (fragment starts with /),
        // behave exactly like $ref (no dynamic resolution)
        assert(is_string($reference));

        if (str_contains($reference, '#/')) {
            $refSchema = ['$ref' => $reference];

            return $this->validateRef($data, $refSchema);
        }

        // Step 1: Resolve statically to find initial target
        $currentBase = $this->getCurrentBaseUri();
        $absoluteRef = $this->resolveUri($currentBase, $reference);

        // Parse URI to separate base from fragment
        $fragment = null;

        if (str_contains((string) $absoluteRef, '#')) {
            [$baseUrl, $fragment] = explode('#', (string) $absoluteRef, 2);
        } else {
            $baseUrl = $absoluteRef;
        }

        // If no fragment or fragment is a JSON Pointer, use $ref behavior
        if ($fragment === null || $fragment === '' || str_starts_with($fragment, '/')) {
            $refSchema = ['$ref' => $reference];

            return $this->validateRef($data, $refSchema);
        }

        // Find the initial target schema (must be an anchor)
        $initialTarget = null;

        if (isset($this->schemaRegistry[$absoluteRef])) {
            $initialTarget = $this->schemaRegistry[$absoluteRef];
        }

        // If initial target not found, fall back to regular $ref
        if ($initialTarget === null) {
            $refSchema = ['$ref' => $reference];

            return $this->validateRef($data, $refSchema);
        }

        // Step 2: Check if initial target has a matching $dynamicAnchor
        $hasDynamicAnchor = isset($initialTarget['$dynamicAnchor'])
                           && $initialTarget['$dynamicAnchor'] === $fragment;

        // If initial target doesn't have matching $dynamicAnchor, use static resolution
        if (!$hasDynamicAnchor) {
            /** @var array<string, mixed>|bool $initialTarget */
            return $this->validateSchema($data, $initialTarget);
        }

        // Step 3: Search dynamic scope for matching $dynamicAnchor
        // Dynamic scope is searched from outermost to innermost
        $dynamicTarget = null;

        foreach ($this->dynamicScope as $scopeEntry) {
            $scopeBaseUri = $scopeEntry['baseUri'] ?? '';

            // Try to resolve the anchor from this scope's base URI
            $candidateUri = mb_rtrim($scopeBaseUri, '#').'#'.$fragment;

            if (!isset($this->schemaRegistry[$candidateUri])) {
                continue;
            }

            $candidateSchema = $this->schemaRegistry[$candidateUri];

            // Check if this schema has the matching $dynamicAnchor
            if (isset($candidateSchema['$dynamicAnchor'])
                && $candidateSchema['$dynamicAnchor'] === $fragment) {
                $dynamicTarget = $candidateSchema;
                $targetType = $candidateSchema['type'] ?? 'unknown';

                break; // Use the FIRST (outermost) match
            }
        }

        // Step 4: Use dynamic target if found, otherwise use static initial target
        $targetSchema = $dynamicTarget ?? $initialTarget;

        return $this->validateSchema($data, $targetSchema);
    }
}
