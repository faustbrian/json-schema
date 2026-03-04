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
use function str_ends_with;
use function str_starts_with;

/**
 * JSON Pointer format validator for JSON Schema.
 *
 * Validates that a value conforms to the JSON Pointer format as defined in RFC 6901.
 * JSON Pointers provide a syntax for identifying a specific value within a JSON
 * document using a string of reference tokens separated by forward slashes.
 *
 * Format rules:
 * - Must start with / or be an empty string (empty = whole document)
 * - Reference tokens are separated by /
 * - ~ must be escaped as ~0
 * - / within a token must be escaped as ~1
 * - Cannot start with # (that's URI Fragment Identifier syntax)
 * - No incomplete escape sequences (~ must be followed by 0 or 1)
 *
 * Valid examples: "", "/foo", "/foo/0", "/a~1b", "/m~0n"
 * Invalid examples: "#/foo", "/foo~", "/foo~2", "foo/bar"
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc6901 RFC 6901: JavaScript Object Notation (JSON) Pointer
 * @see https://datatracker.ietf.org/doc/html/rfc6901#section-3 RFC 6901 Section 3: Syntax
 * @see https://datatracker.ietf.org/doc/html/rfc6901#section-5 RFC 6901 Section 5: JSON String Representation
 */
final readonly class JsonPointerFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the JSON Pointer format.
     *
     * Performs comprehensive validation according to RFC 6901 including:
     * - Empty string validation (points to whole document)
     * - Leading slash requirement for non-empty pointers
     * - Rejection of URI Fragment Identifier syntax (#/...)
     * - Escape sequence validation (~ must be followed by 0 or 1)
     * - Detection of incomplete escape sequences (~ at end of string)
     *
     * @param mixed $value The value to validate as a JSON Pointer
     *
     * @return bool True if the value is a valid JSON Pointer, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is a valid JSON Pointer (points to whole document)
        if ($value === '') {
            return true;
        }

        // URI Fragment Identifier syntax (#...) is NOT valid JSON Pointer
        if (str_starts_with($value, '#')) {
            return false;
        }

        // Must start with /
        if (!str_starts_with($value, '/')) {
            return false;
        }

        // Check for invalid escape sequences
        // ~ must be followed by 0 or 1 (or end of string is invalid)
        if (str_contains($value, '~')) {
            // Find all ~ occurrences
            if (preg_match('/~(?![01])/', $value) === 1) {
                return false;
            }

            // Check for ~ at end of string (incomplete escape)
            if (str_ends_with($value, '~')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'json-pointer'
     */
    public function format(): string
    {
        return 'json-pointer';
    }
}
