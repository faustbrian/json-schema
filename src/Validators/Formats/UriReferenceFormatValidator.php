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
use function mb_strlen;
use function mb_substr_count;
use function ord;
use function str_contains;

/**
 * URI Reference format validator for JSON Schema.
 *
 * Validates that a value conforms to the URI reference format as defined in RFC 3986.
 * URI references are a superset of URIs, allowing both absolute URIs (with schemes)
 * and relative references (without schemes). This format is more permissive than
 * the strict URI format, making it suitable for links and references in documents.
 *
 * Valid formats include:
 * - Absolute URIs: https://example.com/path
 * - Relative paths: /path, ./path, ../path, path
 * - Query only: ?query=value
 * - Fragment only: #section
 * - Empty string: "" (valid URI reference)
 *
 * Validation rules:
 * - No unescaped special characters (<, >, ", {, }, |, \, ^, `, space)
 * - Brackets [ and ] must be balanced (for IPv6 addresses)
 * - Non-ASCII characters must be percent-encoded
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc3986 RFC 3986: Uniform Resource Identifier (URI): Generic Syntax
 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-4.1 RFC 3986 Section 4.1: URI Reference
 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-4.2 RFC 3986 Section 4.2: Relative Reference
 */
final readonly class UriReferenceFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the URI reference format.
     *
     * Performs validation of both absolute URIs and relative references according
     * to RFC 3986. Unlike the strict URI validator, this accepts relative paths,
     * fragment-only references, and empty strings, making it suitable for general
     * web resource references.
     *
     * URI references are validated for:
     * - Character restrictions (no backslashes, quotes, etc.)
     * - ASCII-only (non-ASCII must be percent-encoded)
     * - Balanced brackets for IPv6 addresses
     *
     * @param mixed $value The value to validate as a URI reference
     *
     * @return bool True if the value is a valid URI reference, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is a valid URI reference
        if ($value === '') {
            return true;
        }

        // RFC 3986 disallows these characters unless percent-encoded
        // Note: [ and ] are allowed for IPv6 addresses in authority
        // Backslash is explicitly forbidden (Windows-style paths are invalid)
        $invalidChars = ['<', '>', '"', '{', '}', '|', '\\', '^', '`', ' '];

        foreach ($invalidChars as $char) {
            if (str_contains($value, $char)) {
                return false;
            }
        }

        // Validate that [ and ] are balanced (for IPv6 addresses)
        $openBrackets = mb_substr_count($value, '[');
        $closeBrackets = mb_substr_count($value, ']');

        if ($openBrackets !== $closeBrackets) {
            return false;
        }

        // Check for unescaped non-ASCII characters (characters > 127)
        // RFC 3986 requires non-ASCII to be percent-encoded
        for ($i = 0; $i < mb_strlen($value); ++$i) {
            if (ord($value[$i]) > 127) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'uri-reference'
     */
    public function format(): string
    {
        return 'uri-reference';
    }
}
