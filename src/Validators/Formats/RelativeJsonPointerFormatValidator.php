<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function is_string;
use function preg_match;
use function str_contains;

/**
 * Relative JSON Pointer format validator for JSON Schema.
 *
 * Validates that a value conforms to the Relative JSON Pointer format as defined
 * in the JSON Schema specification draft. Relative JSON Pointers extend JSON Pointers
 * with a non-negative integer prefix indicating upward traversal steps, followed by
 * either a JSON Pointer or an index manipulation marker (#).
 *
 * Format rules:
 * - Must start with a non-negative integer (0, 1, 2, etc.)
 * - Integer cannot have leading zeros (e.g., 01 is invalid, but 0 is valid)
 * - After the integer, must have either:
 *   - A JSON Pointer (starting with /)
 *   - An index marker (#)
 * - Empty strings are invalid
 * - Negative or positive signs are invalid
 *
 * Valid examples: 0/foo/bar, 1/foo, 2/0, 0#, 1#
 * Invalid examples: /foo/bar, -1/foo, +1/foo, 01/a, 0##, ""
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://tools.ietf.org/html/draft-handrews-relative-json-pointer-01 Relative JSON Pointer Draft
 */
final readonly class RelativeJsonPointerFormatValidator implements FormatValidatorInterface
{
    /**
     * Relative JSON Pointer regex pattern.
     *
     * Matches the format: <non-negative-integer>[<json-pointer-or-hash>]
     * - Non-negative integer: 0 or [1-9][0-9]*
     * - Optionally followed by: / (JSON Pointer) or # (index marker)
     * - No leading zeros allowed (except standalone 0)
     */
    private const string RELATIVE_JSON_POINTER_PATTERN = '/^(0|[1-9]\d*)(\/.*|#)?$/';

    /**
     * Validate a value against the Relative JSON Pointer format.
     *
     * Validates the structure of a relative JSON pointer:
     * - Must start with non-negative integer
     * - No leading zeros (except standalone 0)
     * - Optionally continue with / or # (just a number is valid too)
     * - Rejects empty strings, negative/positive signs
     * - Rejects ## (double hash)
     *
     * @param mixed $value The value to validate as a Relative JSON Pointer
     *
     * @return bool True if the value is a valid Relative JSON Pointer, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is invalid
        if ($value === '') {
            return false;
        }

        // Check for ## which is invalid
        if (str_contains($value, '##')) {
            return false;
        }

        // Must match the pattern: <non-negative-integer>[<json-pointer-or-hash>]
        return preg_match(self::RELATIVE_JSON_POINTER_PATTERN, $value) === 1;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'relative-json-pointer'
     */
    public function format(): string
    {
        return 'relative-json-pointer';
    }
}
