<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Contracts;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\ValueObjects\ValidationResult;

/**
 * Contract for JSON Schema validators.
 *
 * Defines the interface for validators that check data against JSON Schema documents.
 * Each validator implementation is specific to a particular JSON Schema draft version
 * and enforces the validation keywords and rules defined by that specification.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/draft/2020-12/json-schema-validation
 * @see https://json-schema.org/understanding-json-schema/reference/index
 */
interface ValidatorInterface
{
    /**
     * Validate data against a schema.
     *
     * Checks whether the provided data conforms to the JSON Schema definition.
     * Returns a ValidationResult containing success status and any validation errors.
     *
     * @param mixed                $data   The data to validate (can be any JSON-serializable value)
     * @param array<string, mixed> $schema The JSON Schema definition as an associative array
     *
     * @return ValidationResult The validation result with status and error details
     */
    public function validate(mixed $data, array $schema): ValidationResult;

    /**
     * Get the draft version supported by this validator.
     *
     * Returns the specific JSON Schema draft specification version that this
     * validator implements. Different drafts support different keywords and
     * validation behaviors.
     *
     * @return Draft The JSON Schema draft version (e.g., Draft07, Draft202012)
     */
    public function supportedDraft(): Draft;
}
