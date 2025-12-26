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
use Override;

use function array_any;
use function array_key_exists;
use function floor;
use function in_array;
use function is_array;
use function is_float;
use function is_infinite;
use function is_nan;
use function is_string;

/**
 * JSON Schema Draft 2019-09 validator implementation.
 *
 * Implements validation according to the JSON Schema Draft 2019-09 specification.
 * Major changes from Draft 07: unevaluatedProperties and unevaluatedItems for stricter
 * validation, split of dependencies into dependentRequired and dependentSchemas,
 * $recursiveRef and $recursiveAnchor for dynamic recursion, and vocabulary-based
 * metaschema system.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/specification-links#2019-09-formerly-known-as-draft-8 JSON Schema Draft 2019-09 Specifications
 * @see https://json-schema.org/draft/2019-09/schema Draft 2019-09 Meta-Schema
 * @see https://json-schema.org/draft/2019-09/json-schema-core Draft 2019-09 Core Specification
 * @see https://json-schema.org/draft/2019-09/json-schema-validation Draft 2019-09 Validation Specification
 * @see https://json-schema.org/understanding-json-schema/reference/index Understanding JSON Schema
 */
final class Draft201909Validator extends AbstractValidator
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

    public function supportedDraft(): Draft
    {
        return Draft::Draft201909;
    }

    /**
     * Filter out keywords introduced in later draft versions.
     *
     * Draft 2019-09 should not process keywords introduced in Draft 2020-12+.
     * This ensures proper cross-draft reference handling.
     *
     * @param  string $methodKeyword The method keyword to check (e.g., 'PrefixItems')
     * @return bool   True if keyword is allowed, false if it should be ignored
     */
    #[Override()]
    protected function isKeywordAllowed(string $methodKeyword): bool
    {
        // Keywords introduced in Draft 2020-12+ that should be ignored in Draft 2019-09
        $disallowedKeywords = [
            'PrefixItems',         // Draft 2020-12+
            'DynamicRef',          // Draft 2020-12+
            'DynamicAnchor',       // Draft 2020-12+
        ];

        if (in_array($methodKeyword, $disallowedKeywords, true)) {
            return false;
        }

        return parent::isKeywordAllowed($methodKeyword);
    }

    /**
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
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
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     */
    protected function validateConst(mixed $data, array $schema): bool
    {
        if (!array_key_exists('const', $schema)) {
            return true;
        }

        return $this->jsonEquals($data, $schema['const']);
    }

    /**
     * Determine if a float value should be treated as an integer (Draft 2019-09+ semantics).
     *
     * Continues Draft 06+ behavior where any float with zero fractional part is considered
     * an integer, including values like 1.0, 2.0, etc. Excludes NaN and infinity.
     *
     * @param mixed $data The value to check for integer representation
     *
     * @return bool True if the float has no fractional part and is finite, false otherwise
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
     * Override content validation to be annotation-only in Draft 2019-09.
     *
     * In Draft 2019-09+, content keywords (contentEncoding, contentMediaType) are
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
        // In Draft 2019-09+, contentEncoding is annotation-only
        return true;
    }

    /**
     * Override content validation to be annotation-only in Draft 2019-09.
     *
     * In Draft 2019-09+, content keywords (contentEncoding, contentMediaType) are
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
        // In Draft 2019-09+, contentMediaType is annotation-only
        return true;
    }

    /**
     * Validate format keyword in Draft 2019-09.
     *
     * In Draft 2019-09, the format vocabulary is optional (false in metaschema).
     * Format validation is controlled by the $enableFormatValidation flag:
     * - When true: Format is validated (for optional/format tests)
     * - When false: Format is annotation-only (for required tests)
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

        // Check if format validation is enabled
        if (!$this->enableFormatValidation) {
            return true;  // Format is annotation-only
        }

        // Format validation is enabled, perform validation
        return $this->performFormatValidation($data, $schema['format']);
    }
}
