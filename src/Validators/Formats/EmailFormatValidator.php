<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use const FILTER_VALIDATE_EMAIL;

use function filter_var;
use function is_string;
use function preg_match;

/**
 * Email format validator for JSON Schema.
 *
 * Validates that a value conforms to the email address format as defined in RFC 5321
 * (SMTP) and RFC 5322 (Internet Message Format). Implements comprehensive email validation
 * that supports both standard formats and RFC 5322 quoted string formats.
 *
 * The validator accepts standard email formats including:
 * - Simple addresses: user@example.com
 * - Subdomains: user@mail.example.com
 * - Plus addressing: user+tag@example.com
 * - Numeric domains: user@192.168.1.1
 * - Quoted strings: "joe bloggs"@example.com
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#email-addresses JSON Schema Email Format
 * @see https://datatracker.ietf.org/doc/html/rfc5321 RFC 5321: Simple Mail Transfer Protocol
 * @see https://datatracker.ietf.org/doc/html/rfc5322 RFC 5322: Internet Message Format
 * @see https://datatracker.ietf.org/doc/html/rfc822 RFC 822: Standard for ARPA Internet Text Messages
 */
final readonly class EmailFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the email format.
     *
     * Validates email addresses using a combination of PHP's FILTER_VALIDATE_EMAIL
     * and custom logic for quoted strings in the local part (per RFC 5322).
     * The filter checks for proper structure including the presence of an @ symbol,
     * valid local and domain parts, and compliance with email address syntax rules.
     *
     * @param mixed $value The value to validate as an email address
     *
     * @return bool True if the value is a valid email address format, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // RFC 5322 allows quoted strings in the local part
        // PHP's filter_var doesn't handle these correctly, so check manually
        if (preg_match('/^".*"@.+$/', $value)) {
            // Find the @ that's after the closing quote of the local part
            // The local part is everything up to and including the first closing quote
            if (preg_match('/^".*?"@(.+)$/', $value, $matches)) {
                $domain = $matches[1];

                // Validate domain part using a simple regex
                return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $domain) === 1;
            }

            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'email'
     */
    public function format(): string
    {
        return 'email';
    }
}
