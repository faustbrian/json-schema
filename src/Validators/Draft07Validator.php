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

/**
 * JSON Schema Draft 07 validator implementation.
 *
 * Implements validation according to the JSON Schema Draft 07 specification.
 * Key changes from Draft 06: if/then/else conditional schema application,
 * readOnly and writeOnly keywords for API documentation, and improved
 * content encoding/media type support.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/specification-links#draft-7 JSON Schema Draft 07 Specifications
 * @see https://json-schema.org/draft-07/schema Draft 07 Meta-Schema
 * @see https://json-schema.org/draft-07/json-schema-core Draft 07 Core Specification
 * @see https://json-schema.org/draft-07/json-schema-validation Draft 07 Validation Specification
 * @see https://json-schema.org/understanding-json-schema/reference/index Understanding JSON Schema
 */
final class Draft07Validator extends AbstractValidator
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
        return Draft::Draft07;
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
     * Validate the const keyword (constant value constraint).
     *
     * Validates that the instance exactly matches the specified constant value using
     * JSON equality semantics. Includes error reporting when validation fails.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/enum#constant-values Understanding JSON Schema - Const
     * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.1.3 Draft-07 - const
     * @param  mixed                $data   The instance to validate against the constant value
     * @param  array<string, mixed> $schema The schema definition containing the const constraint
     * @return bool                 True if the instance equals the const value, false otherwise
     */
    protected function validateConst(mixed $data, array $schema): bool
    {
        if (!array_key_exists('const', $schema)) {
            return true;
        }

        if (!$this->jsonEquals($data, $schema['const'])) {
            $this->addError('const', 'Value must be exactly equal to const value');

            return false;
        }

        return true;
    }

    /**
     * Determine if a float value should be treated as an integer (Draft 07+ semantics).
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
     * Determine if $ref overrides sibling keywords.
     *
     * In Draft 07 and earlier, $ref overrides all sibling keywords.
     */
    #[Override()]
    protected function refOverridesSiblings(): bool
    {
        return true;
    }

    /**
     * Check if a keyword is allowed in Draft 07.
     *
     * Draft 07 predates vocabularies (introduced in Draft 2019-09), so we explicitly
     * filter out keywords that were introduced in later drafts to ensure proper
     * cross-draft validation when a Draft 2019-09+ schema references a Draft 07 schema.
     *
     * @param string $methodKeyword The keyword name in PascalCase (e.g., 'DependentRequired')
     *
     * @return bool True if the keyword is allowed in Draft 07, false otherwise
     */
    #[Override()]
    protected function isKeywordAllowed(string $methodKeyword): bool
    {
        // Keywords introduced in Draft 2019-09+ that should be ignored in Draft 07
        $disallowedKeywords = [
            'DependentRequired',   // Draft 2019-09+
            'DependentSchemas',    // Draft 2019-09+
            'PrefixItems',         // Draft 2020-12+
            'DynamicRef',          // Draft 2020-12+
            'UnevaluatedProperties', // Draft 2019-09+
            'UnevaluatedItems',    // Draft 2019-09+
            'RecursiveRef',        // Draft 2019-09+
        ];

        if (in_array($methodKeyword, $disallowedKeywords, true)) {
            return false;
        }

        return parent::isKeywordAllowed($methodKeyword);
    }
}
