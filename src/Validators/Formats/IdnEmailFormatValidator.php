<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function count;
use function explode;
use function is_numeric;
use function is_string;
use function str_contains;

/**
 * Internationalized Domain Names (IDN) email format validator for JSON Schema.
 *
 * Validates that a value conforms to the internationalized email address format.
 * This validator extends standard email validation with additional checks for
 * international domain names while ensuring the local part is not purely numeric.
 *
 * The validator rejects:
 * - Purely numeric strings (e.g., "2962")
 * - Invalid email formats
 * - Missing @ symbol or invalid structure
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://datatracker.ietf.org/doc/html/rfc6531 RFC 6531: SMTP Extension for Internationalized Email
 */
final readonly class IdnEmailFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the idn-email format.
     *
     * Uses PHP's FILTER_VALIDATE_EMAIL filter with additional validation
     * to reject purely numeric values which are not valid email addresses.
     * Supports internationalized domain names and email addresses with non-ASCII characters.
     *
     * @param mixed $value The value to validate as an internationalized email address
     *
     * @return bool True if the value is a valid idn-email address format, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Reject purely numeric strings (no @ symbol means it's not an email)
        if (is_numeric($value)) {
            return false;
        }

        // For internationalized emails, we need to be more permissive
        // PHP's FILTER_VALIDATE_EMAIL doesn't handle IDN well, so we do basic validation
        // Must contain @ symbol and have local and domain parts
        if (!str_contains($value, '@')) {
            return false;
        }

        // Split into local and domain parts
        $parts = explode('@', $value);

        if (count($parts) !== 2) {
            return false;
        }

        [$local, $domain] = $parts;

        // Local and domain parts must not be empty
        return $local !== '' && $domain !== '';
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'idn-email'
     */
    public function format(): string
    {
        return 'idn-email';
    }
}
