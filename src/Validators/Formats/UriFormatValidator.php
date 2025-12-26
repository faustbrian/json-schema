<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function explode;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function mb_substr_count;
use function ord;
use function preg_match;
use function str_contains;

/**
 * URI format validator for JSON Schema.
 *
 * Validates that a value conforms to the URI (Uniform Resource Identifier) format
 * as defined in RFC 3986. URIs must include a scheme (e.g., http, https, ftp) and
 * follow specific syntax rules including character restrictions, bracket balancing
 * for IPv6 addresses, and proper percent-encoding for non-ASCII characters.
 *
 * Validation rules:
 * - Must include a scheme (e.g., http:, https:, ftp:)
 * - Scheme format: ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
 * - No unescaped special characters (<, >, ", {, }, |, \, ^, `, space)
 * - Brackets [ and ] must be balanced (for IPv6 addresses)
 * - Non-ASCII characters (>127) must be percent-encoded
 *
 * Valid examples: https://example.com, ftp://ftp.example.com, mailto:user@example.com
 * Invalid examples: //example.com, example.com, http://ex ample.com
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc3986 RFC 3986: Uniform Resource Identifier (URI): Generic Syntax
 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-3 RFC 3986 Section 3: Syntax Components
 */
final readonly class UriFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the URI format.
     *
     * Performs comprehensive URI validation according to RFC 3986 including:
     * - Scheme presence and format validation (must start with letter)
     * - Character restrictions (no unescaped special characters)
     * - Bracket balancing for IPv6 addresses in authority component
     * - ASCII-only requirement (non-ASCII must be percent-encoded)
     * - Proper URI structure verification
     * - Rejects relative references and protocol-relative URIs
     *
     * @param mixed $value The value to validate as a URI
     *
     * @return bool True if the value is a valid URI, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is not a valid URI
        if ($value === '') {
            return false;
        }

        // RFC 3986 disallows these characters in URIs unless percent-encoded
        // Note: [ and ] are allowed for IPv6 addresses in authority component
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
        // RFC 3986 requires non-ASCII characters to be percent-encoded
        for ($i = 0; $i < mb_strlen($value); ++$i) {
            if (ord($value[$i]) > 127) {
                return false;
            }
        }

        // URI must have a scheme
        // RFC 3986: scheme = ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
        // The scheme is followed by a colon
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $value, $matches)) {
            return false;
        }

        $scheme = $matches[0];
        $afterScheme = mb_substr($value, mb_strlen($scheme));

        // After the scheme, there must be something (not just the colon)
        if ($afterScheme === '') {
            return false;
        }

        // Validate userinfo if present (before @)
        // Userinfo cannot contain invalid characters like [@
        if (str_contains($afterScheme, '@')) {
            $parts = explode('@', $afterScheme);
            $beforeAt = $parts[0];

            // Check if userinfo contains invalid characters like [
            if (str_contains($beforeAt, '[')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'uri'
     */
    public function format(): string
    {
        return 'uri';
    }
}
