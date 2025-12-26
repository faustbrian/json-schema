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
use function ctype_digit;
use function explode;
use function is_string;
use function mb_strlen;
use function preg_match;
use function str_contains;

/**
 * IPv4 address format validator for JSON Schema.
 *
 * Validates that a value conforms to the IPv4 address format as defined in RFC 2673.
 * Uses PHP's built-in FILTER_VALIDATE_IP filter with IPv4 flag for standards-compliant
 * validation. IPv4 addresses consist of four decimal octets separated by dots, where
 * each octet ranges from 0 to 255.
 *
 * Valid examples: 192.168.1.1, 10.0.0.1, 255.255.255.255, 0.0.0.0
 * Invalid examples: 256.1.1.1, 192.168.1, 192.168.1.1.1, 192.168.1.-1
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#ip-addresses JSON Schema IP Address Formats
 * @see https://datatracker.ietf.org/doc/html/rfc2673 RFC 2673: Binary Labels in the Domain Name System
 * @see https://datatracker.ietf.org/doc/html/rfc791 RFC 791: Internet Protocol
 */
final readonly class Ipv4FormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the IPv4 format.
     *
     * Performs strict RFC 791 compliant IPv4 validation including:
     * - Exactly 4 octets separated by dots
     * - Each octet is 0-255 with no leading zeros
     * - No special formats (hex, decimal, CIDR)
     * - ASCII only (no Unicode digits)
     *
     * @param mixed $value The value to validate as an IPv4 address
     *
     * @return bool True if the value is a valid IPv4 address, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for non-ASCII characters
        if (preg_match('/[^\x00-\x7F]/', $value)) {
            return false;
        }

        // Reject CIDR notation, hex, or other special formats
        if (str_contains($value, '/') || str_contains($value, 'x') || str_contains($value, 'X')) {
            return false;
        }

        // Split into octets
        $octets = explode('.', $value);

        // Must have exactly 4 octets
        if (count($octets) !== 4) {
            return false;
        }

        foreach ($octets as $octet) {
            // Must be non-empty
            if ($octet === '') {
                return false;
            }

            // Must contain only digits
            if (!ctype_digit($octet)) {
                return false;
            }

            // No leading zeros (except for '0' itself)
            if (mb_strlen($octet) > 1 && $octet[0] === '0') {
                return false;
            }

            // Must be in range 0-255
            $num = (int) $octet;

            if ($num < 0 || $num > 255) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'ipv4'
     */
    public function format(): string
    {
        return 'ipv4';
    }
}
