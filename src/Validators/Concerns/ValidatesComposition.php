<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use function is_array;

/**
 * Composition validation logic for JSON Schema.
 *
 * Implements validation for schema composition keywords: allOf, anyOf, oneOf, and not.
 * These keywords allow combining multiple schemas with Boolean logic.
 *
 * Properly handles annotation collection per the JSON Schema specification:
 * - allOf: Collects annotations from ALL branches
 * - anyOf: Collects annotations from ALL successful branches
 * - oneOf: Collects annotations from the ONE successful branch
 * - not: Does NOT collect annotations (negation)
 *
 * Each composition branch is evaluated in isolation for unevaluatedProperties/Items,
 * preventing incorrect annotation leaking between sibling branches.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/understanding-json-schema/reference/combining Schema composition guide
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.2.1 Composition keywords specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.2.1.1 allOf keyword
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.2.1.2 anyOf keyword
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.2.1.3 oneOf keyword
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-10.2.2.4 not keyword
 */
trait ValidatesComposition
{
    /**
     * Validate allOf keyword.
     *
     * Data must be valid against ALL subschemas. Each branch is evaluated in
     * isolation for proper unevaluatedProperties/Items behavior, but annotations
     * from all branches are merged per the specification.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/combining#allOf
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid against all subschemas, false otherwise
     */
    protected function validateAllOf(mixed $data, array $schema): bool
    {
        if (!isset($schema['allOf']) || !is_array($schema['allOf'])) {
            return true;
        }

        // Save base state (properties evaluated by parent schema)
        $baseState = $this->saveEvaluationState();
        $branchStates = [];

        // Empty state for proper schema-level isolation
        // Each allOf branch is a separate schema object and should not see
        // parent annotations for unevaluated* checks (per JSON Schema spec)
        $emptyState = ['properties' => [], 'items' => []];

        foreach ($schema['allOf'] as $subSchema) {
            // Each branch starts with EMPTY annotations (schema scope isolation)
            // This ensures unevaluated* keywords only see annotations from their own scope
            $this->restoreEvaluationState($emptyState);

            /** @var array<string, mixed>|bool $subSchema */
            if (!$this->validateSchema($data, $subSchema)) {
                // Restore state and fail
                $this->restoreEvaluationState($baseState);

                return false;
            }

            // Save this branch's annotations
            $branchStates[] = $this->saveEvaluationState();
        }

        // Restore base state, then merge all branches' annotations
        $this->restoreEvaluationState($baseState);

        foreach ($branchStates as $branchState) {
            $this->mergeEvaluationState($branchState);
        }

        return true;
    }

    /**
     * Validate anyOf keyword.
     *
     * Data must be valid against AT LEAST ONE subschema. Annotations are collected
     * from ALL successful branches (not just the first match) per the specification.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/combining#anyOf
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid against one or more subschemas, false otherwise
     */
    protected function validateAnyOf(mixed $data, array $schema): bool
    {
        if (!isset($schema['anyOf']) || !is_array($schema['anyOf'])) {
            return true;
        }

        $baseState = $this->saveEvaluationState();
        $successfulBranches = [];
        $anyValid = false;

        // Empty state for schema-level isolation
        $emptyState = ['properties' => [], 'items' => []];

        foreach ($schema['anyOf'] as $subSchema) {
            // Each branch starts with EMPTY annotations (schema scope isolation)
            $this->restoreEvaluationState($emptyState);

            /** @var array<string, mixed>|bool $subSchema */
            if (!$this->validateSchema($data, $subSchema)) {
                continue;
            }

            // Save the state after successful validation
            $successfulBranches[] = $this->saveEvaluationState();
            $anyValid = true;
        }

        if (!$anyValid) {
            // No branches succeeded, restore base state
            $this->restoreEvaluationState($baseState);

            return false;
        }

        // Restore base state, then merge all successful branches
        $this->restoreEvaluationState($baseState);

        foreach ($successfulBranches as $branchState) {
            $this->mergeEvaluationState($branchState);
        }

        return true;
    }

    /**
     * Validate oneOf keyword.
     *
     * Data must be valid against EXACTLY ONE subschema - not zero, not more than one.
     * Annotations are collected only from the single successful branch.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/combining#oneOf
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid against exactly one subschema, false otherwise
     */
    protected function validateOneOf(mixed $data, array $schema): bool
    {
        if (!isset($schema['oneOf']) || !is_array($schema['oneOf'])) {
            return true;
        }

        $baseState = $this->saveEvaluationState();
        $successfulBranchState = null;
        $validCount = 0;

        // Empty state for schema-level isolation
        $emptyState = ['properties' => [], 'items' => []];

        foreach ($schema['oneOf'] as $subSchema) {
            // Each branch starts with EMPTY annotations (schema scope isolation)
            $this->restoreEvaluationState($emptyState);

            /** @var array<string, mixed>|bool $subSchema */
            if (!$this->validateSchema($data, $subSchema)) {
                continue;
            }

            ++$validCount;

            // Save the first successful branch state
            if ($validCount !== 1) {
                continue;
            }

            $successfulBranchState = $this->saveEvaluationState();
        }

        // Restore base state
        $this->restoreEvaluationState($baseState);

        if ($validCount !== 1) {
            return false;
        }

        // Merge the one successful branch's annotations
        if ($successfulBranchState !== null) {
            $this->mergeEvaluationState($successfulBranchState);
        }

        return true;
    }

    /**
     * Validate not keyword.
     *
     * Data must NOT be valid against the negated schema. This is the logical
     * negation operator. Annotations from the negated schema are discarded
     * per the specification (they don't contribute to evaluation tracking).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/combining#not
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if data fails validation against negated schema, false otherwise
     */
    protected function validateNot(mixed $data, array $schema): bool
    {
        if (!isset($schema['not'])) {
            return true;
        }

        // Save state before validating "not" schema
        $baseState = $this->saveEvaluationState();

        // Validate against the negated schema
        /** @var array<string, mixed>|bool $notSchema */
        $notSchema = $schema['not'];
        $result = $this->validateSchema($data, $notSchema);

        // Restore state (discard any annotations from "not" schema)
        $this->restoreEvaluationState($baseState);

        return !$result;
    }
}
