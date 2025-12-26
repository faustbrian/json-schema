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
use function preg_match;
use function str_contains;

/**
 * IRI (Internationalized Resource Identifier) format validator for JSON Schema.
 *
 * Validates that a value conforms to the IRI format as defined in RFC 3987.
 * IRIs extend URIs by allowing Unicode characters, making them suitable for
 * internationalized web addresses and identifiers.
 *
 * Validation rules:
 * - Must include a scheme (e.g., http:, https:, ftp:)
 * - Scheme format: ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
 * - Allows Unicode characters (unlike URI)
 * - Still disallows certain special characters (<, >, ", {, }, |, \, ^, `)
 * - Brackets [ and ] must be balanced (for IPv6 addresses)
 *
 * Valid examples: https://example.com, http://例え.jp, ftp://tëst.com
 * Invalid examples: //example.com, /path, \\WINDOWS\file
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc3987 RFC 3987: Internationalized Resource Identifiers (IRIs)
 */
final readonly class IriFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the IRI format.
     *
     * Performs IRI validation according to RFC 3987 including:
     * - Scheme presence and format validation
     * - Character restrictions (no unescaped special characters like backslash)
     * - Bracket balancing for IPv6 addresses
     * - Unicode support (unlike URI which is ASCII-only)
     * - Rejects relative references
     *
     * @param mixed $value The value to validate as an IRI
     *
     * @return bool True if the value is a valid IRI, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is not a valid IRI
        if ($value === '') {
            return false;
        }

        // RFC 3987 disallows these characters in IRIs
        // Note: [ and ] are allowed for IPv6 addresses in authority component
        // Backslash is explicitly forbidden
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

        // Check for unescaped colons in host part that might indicate an invalid IPv6 address
        // IPv6 addresses in IRIs must be enclosed in brackets
        // e.g., http://[2001:db8::1]/ is valid, http://2001:db8::1/ is invalid
        // We need to check the host part only (after userinfo@ if present)
        // Pattern: // followed by optional userinfo@, then host with multiple colons not in brackets
        if (preg_match('~//(?:[^@/]*@)?([^\[\]/@?#]*)~', $value, $matches)) {
            $host = $matches[1];

            // If host has multiple colons, it's likely an IPv6 without brackets
            if (mb_substr_count($host, ':') >= 2) {
                return false;
            }
        }

        // IRI must have a scheme (unlike IRI reference)
        // RFC 3987: scheme = ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
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
     * @return string The format identifier 'iri'
     */
    public function format(): string
    {
        return 'iri';
    }
}
