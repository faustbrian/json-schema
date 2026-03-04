<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Contracts;

/**
 * Contract for JSON Schema format validators.
 *
 * Defines the interface for validators that check string values against
 * specific format constraints (e.g., email, uri, uuid). Format validators
 * implement semantic validation beyond basic type checking.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/string#format
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-7
 */
interface FormatValidatorInterface
{
    /**
     * Validate a value against the format.
     *
     * Checks whether the provided value conforms to the specific format
     * rules implemented by this validator.
     *
     * @param mixed $value The value to validate
     *
     * @return bool True if the value matches the format, false otherwise
     */
    public function validate(mixed $value): bool;

    /**
     * Get the format name.
     *
     * Returns the format identifier as specified in JSON Schema
     * (e.g., 'email', 'uri', 'uuid').
     *
     * @return string The format identifier
     */
    public function format(): string;
}
