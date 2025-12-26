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
use function is_array;
use function is_float;
use function is_int;

/**
 * JSON Schema Draft 04 validator implementation.
 *
 * Implements validation according to the JSON Schema Draft 04 specification.
 * Key characteristics: exclusiveMinimum/exclusiveMaximum are boolean modifiers
 * rather than standalone values, $ref overrides all sibling keywords, and
 * integer type only matches bignums for floats (not 1.0).
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/specification-links#draft-4 JSON Schema Draft 04 Specifications
 * @see https://json-schema.org/draft-04/schema Draft 04 Meta-Schema
 * @see https://json-schema.org/draft-04/json-schema-core Draft 04 Core Specification
 * @see https://json-schema.org/draft-04/json-schema-validation Draft 04 Validation Specification
 * @see https://json-schema.org/understanding-json-schema/reference/index Understanding JSON Schema
 */
final class Draft04Validator extends AbstractValidator
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
        return Draft::Draft04;
    }

    /**
     * Validate the enum keyword (enumeration of allowed values).
     *
     * Validates that the instance matches one of the allowed values using JSON
     * equality semantics (deep structural comparison, not reference equality).
     *
     * @see https://json-schema.org/understanding-json-schema/reference/enum Understanding JSON Schema - Enum
     * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.5.1 Draft-04 - enum
     * @param  mixed                $data   The instance to validate against the enumeration
     * @param  array<string, mixed> $schema The schema definition containing the enum constraint
     * @return bool                 True if the instance matches at least one enum value, false otherwise
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
     * Validate the maximum keyword with Draft 04 semantics.
     *
     * In Draft 04, exclusiveMaximum is a boolean modifier that changes maximum from
     * inclusive to exclusive. When exclusiveMaximum is true, the value must be
     * strictly less than maximum; otherwise, less than or equal to maximum.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range Understanding JSON Schema - Numeric Range
     * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.1.3 Draft-04 - maximum and exclusiveMaximum
     * @param  mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the maximum constraint
     * @return bool                 True if the value satisfies the maximum constraint, false otherwise
     */
    protected function validateMaximum(mixed $data, array $schema): bool
    {
        if (!isset($schema['maximum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        $exclusive = isset($schema['exclusiveMaximum']) && $schema['exclusiveMaximum'] === true;

        if ($exclusive) {
            return $data < $schema['maximum'];
        }

        return $data <= $schema['maximum'];
    }

    /**
     * Validate the minimum keyword with Draft 04 semantics.
     *
     * In Draft 04, exclusiveMinimum is a boolean modifier that changes minimum from
     * inclusive to exclusive. When exclusiveMinimum is true, the value must be
     * strictly greater than minimum; otherwise, greater than or equal to minimum.
     *
     * @param mixed                $data   The instance to validate (must be numeric for constraint to apply)
     * @param array<string, mixed> $schema The schema definition containing the minimum constraint
     *
     * @return bool True if the value satisfies the minimum constraint, false otherwise
     */
    protected function validateMinimum(mixed $data, array $schema): bool
    {
        if (!isset($schema['minimum']) || !is_int($data) && !is_float($data)) {
            return true;
        }

        $exclusive = isset($schema['exclusiveMinimum']) && $schema['exclusiveMinimum'] === true;

        if ($exclusive) {
            return $data > $schema['minimum'];
        }

        return $data >= $schema['minimum'];
    }

    /**
     * Validate exclusiveMaximum keyword (Draft 04 semantics).
     *
     * In Draft 04, this is a boolean modifier for maximum, not a standalone constraint.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateExclusiveMaximum(mixed $data, array $schema): bool
    {
        // In Draft 04, exclusiveMaximum is handled by validateMaximum
        return true;
    }

    /**
     * Validate exclusiveMinimum keyword (Draft 04 semantics).
     *
     * In Draft 04, this is a boolean modifier for minimum, not a standalone constraint.
     *
     * @param mixed                $data   The data to validate
     * @param array<string, mixed> $schema The schema definition
     *
     * @return bool True if valid
     */
    protected function validateExclusiveMinimum(mixed $data, array $schema): bool
    {
        // In Draft 04, exclusiveMinimum is handled by validateMinimum
        return true;
    }

    /**
     * In Draft 04, $ref overrides all sibling keywords.
     */
    #[Override()]
    protected function refOverridesSiblings(): bool
    {
        return true;
    }
}
