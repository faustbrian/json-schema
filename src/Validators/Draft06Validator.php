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
use Cline\JsonSchema\Validators\Concerns\ValidatesNumbers;
use Cline\JsonSchema\Validators\Concerns\ValidatesObjects;
use Cline\JsonSchema\Validators\Concerns\ValidatesReferences;
use Cline\JsonSchema\Validators\Concerns\ValidatesStrings;
use Cline\JsonSchema\Validators\Concerns\ValidatesTypes;
use Override;

use function array_any;
use function array_key_exists;
use function floor;
use function is_array;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;

/**
 * JSON Schema Draft 06 validator implementation.
 *
 * Implements validation according to the JSON Schema Draft 06 specification.
 * Key changes from Draft 04: const keyword for single value constraints, contains
 * keyword for array validation, exclusiveMinimum/exclusiveMaximum as numeric values
 * instead of boolean modifiers, and integer type matches floats like 1.0.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/specification-links#draft-6 JSON Schema Draft 06 Specifications
 * @see https://json-schema.org/draft-06/schema Draft 06 Meta-Schema
 * @see https://json-schema.org/draft-06/json-schema-core Draft 06 Core Specification
 * @see https://json-schema.org/draft-06/json-schema-validation Draft 06 Validation Specification
 * @see https://json-schema.org/understanding-json-schema/reference/index Understanding JSON Schema
 */
final class Draft06Validator extends AbstractValidator
{
    use ComparesJsonValues;
    use ValidatesArrays;
    use ValidatesComposition;
    use ValidatesNumbers;
    use ValidatesObjects;
    use ValidatesStrings;
    use ValidatesTypes;
    use ValidatesReferences;

    public function supportedDraft(): Draft
    {
        return Draft::Draft06;
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
     * JSON equality semantics (deep structural comparison). Similar to enum with a
     * single allowed value but more explicit and clearer in intent.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/enum#constant-values Understanding JSON Schema - Const
     * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.1.3 Draft-06 - const
     * @param  mixed                $data   The instance to validate against the constant value
     * @param  array<string, mixed> $schema The schema definition containing the const constraint
     * @return bool                 True if the instance equals the const value, false otherwise
     */
    protected function validateConst(mixed $data, array $schema): bool
    {
        if (!array_key_exists('const', $schema)) {
            return true;
        }

        return $this->jsonEquals($data, $schema['const']);
    }

    /**
     * Validate exclusiveMaximum keyword (Draft 06+ semantics).
     *
     * In Draft 06+, exclusiveMaximum is a standalone numeric value, not a boolean.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.2.3 Draft-06 - exclusiveMaximum
     * @param  mixed                $data   The data to validate
     * @param  array<string, mixed> $schema The schema definition
     * @return bool                 True if valid
     */
    protected function validateExclusiveMaximum(mixed $data, array $schema): bool
    {
        if (!isset($schema['exclusiveMaximum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        return $data < $schema['exclusiveMaximum'];
    }

    /**
     * Validate exclusiveMinimum keyword (Draft 06+ semantics).
     *
     * In Draft 06+, exclusiveMinimum is a standalone numeric value, not a boolean.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateExclusiveMinimum(mixed $data, array $schema): bool
    {
        if (!isset($schema['exclusiveMinimum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        return $data > $schema['exclusiveMinimum'];
    }

    /**
     * Determine if a float value should be treated as an integer (Draft 06+ semantics).
     *
     * In Draft 06 and later, any float with zero fractional part is considered an integer,
     * including values like 1.0, 2.0, etc. This is more permissive than Draft 04 which only
     * accepted bignums (floats exceeding PHP_INT_MAX). Excludes NaN and infinity.
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
     * In Draft 06, $ref overrides all sibling keywords.
     */
    #[Override()]
    protected function refOverridesSiblings(): bool
    {
        return true;
    }
}
