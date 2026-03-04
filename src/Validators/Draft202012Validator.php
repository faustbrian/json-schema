<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Concerns\ComparesJsonValues;
use Cline\JsonSchema\Validators\Concerns\ValidatesArrays;
use Cline\JsonSchema\Validators\Concerns\ValidatesComposition;
use Cline\JsonSchema\Validators\Concerns\ValidatesConditionals;
use Cline\JsonSchema\Validators\Concerns\ValidatesNumbers;
use Cline\JsonSchema\Validators\Concerns\ValidatesObjects;
use Cline\JsonSchema\Validators\Concerns\ValidatesReferences;
use Cline\JsonSchema\Validators\Concerns\ValidatesStrings;
use Cline\JsonSchema\Validators\Concerns\ValidatesTypes;

use function array_any;
use function array_key_exists;
use function floor;
use function is_array;
use function is_float;
use function is_infinite;
use function is_nan;
use function is_string;

/**
 * JSON Schema Draft 2020-12 validator.
 *
 * Implements validation for JSON Schema Draft 2020-12 (the latest stable specification).
 * This draft introduces several new keywords including prefixItems for tuple validation,
 * $dynamicRef/$dynamicAnchor for dynamic schema resolution, and enhanced enum/const
 * handling with improved type coercion (e.g., treating 1.0 as an integer).
 *
 * Key features of Draft 2020-12:
 * - prefixItems: Validates array items by position (tuple validation)
 * - $dynamicRef/$dynamicAnchor: Dynamic reference resolution for recursive schemas
 * - Enhanced integer detection: Floats with zero fractional part (1.0) are treated as integers
 * - Improved const/enum validation with strict JSON equality semantics
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core 2020-12 Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation 2020-12 Specification
 * @see https://json-schema.org/understanding-json-schema/reference/schema Understanding JSON Schema Draft 2020-12
 * @see https://json-schema.org/draft/2020-12/release-notes JSON Schema Draft 2020-12 Release Notes
 */
final class Draft202012Validator extends AbstractValidator
{
    use ComparesJsonValues;
    use ValidatesArrays;
    use ValidatesComposition;
    use ValidatesConditionals;
    use ValidatesNumbers;
    use ValidatesObjects;
    use ValidatesStrings;
    use ValidatesTypes;
    use ValidatesReferences;

    /**
     * Get the JSON Schema draft version supported by this validator.
     *
     * @return Draft The Draft 2020-12 enum value
     */
    public function supportedDraft(): Draft
    {
        return Draft::Draft202012;
    }

    /**
     * Validate data against the enum keyword.
     *
     * Checks if the data exactly matches one of the values in the enum array
     * using strict JSON equality semantics. The comparison accounts for type
     * differences (e.g., 1 vs "1" vs 1.0 vs true are all distinct values).
     *
     * @param mixed                $data   The data to validate against the enum
     * @param array<string, mixed> $schema The schema containing the enum keyword
     *
     * @return bool True if data matches an enum value or enum is not present, false otherwise
     */
    protected function validateEnum(mixed $data, array $schema): bool
    {
        if (!isset($schema['enum'])) {
            return true;
        }

        if (!is_array($schema['enum'])) {
            return false;
        }

        return array_any($schema['enum'], fn ($value): bool => $this->jsonEquals($data, $value));
    }

    /**
     * Validate data against the const keyword.
     *
     * Checks if the data exactly matches the const value using strict JSON
     * equality semantics. The const keyword restricts a value to a single
     * specific value (unlike enum which allows multiple possible values).
     *
     * @param mixed                $data   The data to validate against the const value
     * @param array<string, mixed> $schema The schema containing the const keyword
     *
     * @return bool True if data matches const value or const is not present, false otherwise
     */
    protected function validateConst(mixed $data, array $schema): bool
    {
        if (!array_key_exists('const', $schema)) {
            return true;
        }

        return $this->jsonEquals($data, $schema['const']);
    }

    /**
     * Check if a float value represents an integer.
     *
     * In Draft 2020-12 and later, floats with zero fractional part (e.g., 1.0, 2.0)
     * are considered integers for type validation purposes. This differs from earlier
     * drafts where 1.0 would only match type: "number", not type: "integer".
     *
     * @param mixed $data The value to check for integer-like float status
     *
     * @return bool True if value is a float with no fractional part, false otherwise
     */
    protected function isIntegerFloat(mixed $data): bool
    {
        if (!is_float($data)) {
            return false;
        }

        // Check if the float has no fractional part
        return floor($data) === $data && !is_nan($data) && !is_infinite($data);
    }

    /**
     * Override content validation to be annotation-only in Draft 2020-12.
     *
     * In Draft 2020-12, content keywords (contentEncoding, contentMediaType) are
     * annotation-only by default. They describe the content but don't cause validation
     * to fail. This differs from Draft 7 where content validation causes failures.
     *
     * @param mixed                $data   The instance to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool Always returns true (annotation-only)
     */
    protected function validateContentEncoding(mixed $data, array $schema): bool
    {
        // In Draft 2020-12, contentEncoding is annotation-only
        return true;
    }

    /**
     * Override content validation to be annotation-only in Draft 2020-12.
     *
     * In Draft 2020-12, content keywords (contentEncoding, contentMediaType) are
     * annotation-only by default. They describe the content but don't cause validation
     * to fail. This differs from Draft 7 where content validation causes failures.
     *
     * @param mixed                $data   The instance to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool Always returns true (annotation-only)
     */
    protected function validateContentMediaType(mixed $data, array $schema): bool
    {
        // In Draft 2020-12, contentMediaType is annotation-only
        return true;
    }

    /**
     * Validate format keyword in Draft 2020-12.
     *
     * In Draft 2020-12, format-annotation (annotation-only) is the default vocabulary.
     * Format validation is controlled by:
     * - The $enableFormatValidation flag (for optional/format tests)
     * - The metaschema's format-assertion vocabulary declaration (custom metaschemas)
     *
     * If either condition is true, format validation happens. Otherwise, format is annotation-only.
     *
     * @param mixed                $data   The instance to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if format validation passes or is disabled
     */
    protected function validateFormat(mixed $data, array $schema): bool
    {
        if (!isset($schema['format']) || !is_string($data) || !is_string($schema['format'])) {
            return true;
        }

        // Check if format-assertion vocabulary is declared in the metaschema
        $formatAssertionUri = 'https://json-schema.org/draft/2020-12/vocab/format-assertion';
        $hasFormatAssertion = isset($this->metaschemaVocabularies[$formatAssertionUri]);

        // Enable validation if either:
        // 1. The global flag is enabled (for optional/format tests)
        // 2. The metaschema explicitly declares format-assertion vocabulary
        if (!$this->enableFormatValidation && !$hasFormatAssertion) {
            return true;  // Format is annotation-only
        }

        // Format validation is enabled, perform validation
        return $this->performFormatValidation($data, $schema['format']);
    }
}
