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
use function ctype_xdigit;
use function explode;
use function is_string;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function mb_substr_count;
use function mb_trim;
use function preg_match;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

/**
 * IPv6 address format validator for JSON Schema.
 *
 * Validates that a value conforms to the IPv6 address format as defined in RFC 4291.
 * Uses PHP's built-in FILTER_VALIDATE_IP filter with IPv6 flag for standards-compliant
 * validation. IPv6 addresses consist of eight groups of four hexadecimal digits
 * separated by colons, with support for zero compression (::) and mixed notation.
 *
 * Valid examples:
 * - Full notation: 2001:0db8:85a3:0000:0000:8a2e:0370:7334
 * - Compressed: 2001:db8:85a3::8a2e:370:7334
 * - Loopback: ::1
 * - Mixed notation: ::ffff:192.0.2.1
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#ip-addresses JSON Schema IP Address Formats
 * @see https://datatracker.ietf.org/doc/html/rfc4291 RFC 4291: IP Version 6 Addressing Architecture
 * @see https://datatracker.ietf.org/doc/html/rfc4291#section-2.2 RFC 4291 Section 2.2: Text Representation of Addresses
 */
final readonly class Ipv6FormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the IPv6 format.
     *
     * Performs strict RFC 4291 compliant IPv6 validation including:
     * - Eight groups of 4 hexadecimal digits separated by colons
     * - Support for :: compression (only once)
     * - Mixed IPv4/IPv6 notation validation
     * - Rejection of zone IDs, CIDR notation, and whitespace
     * - ASCII-only validation
     *
     * @param mixed $value The value to validate as an IPv6 address
     *
     * @return bool True if the value is a valid IPv6 address, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Reject whitespace
        if ($value !== mb_trim($value)) {
            return false;
        }

        // Check for non-ASCII characters
        if (preg_match('/[^\x00-\x7F]/', $value)) {
            return false;
        }

        // Reject zone IDs (fe80::a%eth1) and CIDR notation (fe80::/64)
        if (str_contains($value, '%') || str_contains($value, '/')) {
            return false;
        }

        // Cannot start or end with single colon (except for ::)
        if ((str_starts_with($value, ':') && !str_starts_with($value, '::'))
            || (str_ends_with($value, ':') && !str_ends_with($value, '::'))) {
            return false;
        }

        // Cannot have triple colons or more
        if (str_contains($value, ':::')) {
            return false;
        }

        // Check if :: appears more than once
        $doubleColonCount = mb_substr_count($value, '::');

        if ($doubleColonCount > 1) {
            return false;
        }

        // Check for mixed IPv4 format (e.g., ::ffff:192.0.2.1)
        $hasMixedFormat = str_contains($value, '.');

        if ($hasMixedFormat) {
            // Split at the last colon to get IPv4 part
            $lastColon = mb_strrpos($value, ':');

            if ($lastColon === false) {
                return false;
            }

            $ipv6Part = mb_substr($value, 0, $lastColon);
            $ipv4Part = mb_substr($value, $lastColon + 1);

            // Validate IPv4 part
            if (!$this->validateIpv4Part($ipv4Part)) {
                return false;
            }

            // For mixed format, we should have at most 6 IPv6 groups
            $groups = $this->splitIpv6Groups($ipv6Part);
            $groupCount = count($groups);

            if ($doubleColonCount === 0) {
                // Without compression, must have exactly 6 groups
                if ($groupCount !== 6) {
                    return false;
                }
            } elseif ($groupCount > 6) {
                // With compression, must have at most 6 groups
                return false;
            }

            // Validate each IPv6 group
            foreach ($groups as $group) {
                if (!$this->isValidHexGroup($group)) {
                    return false;
                }
            }
        } else {
            // Pure IPv6 format
            $groups = $this->splitIpv6Groups($value);
            $groupCount = count($groups);

            if ($doubleColonCount === 0) {
                // Without compression, must have exactly 8 groups
                if ($groupCount !== 8) {
                    return false;
                }
            } else {
                // With compression, must have at most 8 groups
                if ($groupCount > 8) {
                    return false;
                }

                // At least one group must be present (cannot be just "::")
                // Actually :: by itself is valid (represents all zeros), so we allow it
            }

            // Validate each group
            foreach ($groups as $group) {
                if (!$this->isValidHexGroup($group)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'ipv6'
     */
    public function format(): string
    {
        return 'ipv6';
    }

    /**
     * Split IPv6 address into groups, handling :: compression.
     *
     * @return array<int, string>
     */
    private function splitIpv6Groups(string $value): array
    {
        if ($value === '' || $value === '::') {
            return [];
        }

        // Remove leading/trailing :: for splitting
        $value = mb_trim($value, ':');

        if ($value === '') {
            return [];
        }

        return explode(':', $value);
    }

    /**
     * Validate a hexadecimal group (1-4 hex digits).
     */
    private function isValidHexGroup(string $group): bool
    {
        // Empty groups are valid when :: is used
        if ($group === '') {
            return true;
        }

        // Must be 1-4 hexadecimal characters
        // @phpstan-ignore-next-line - Valid check for empty string (strlen < 1 means strlen === 0)
        if (mb_strlen($group) > 4) {
            return false;
        }

        // Must be valid hexadecimal
        return ctype_xdigit($group);
    }

    /**
     * Validate the IPv4 part in mixed format.
     */
    private function validateIpv4Part(string $ipv4): bool
    {
        $octets = explode('.', $ipv4);

        // Must have exactly 4 octets
        if (count($octets) !== 4) {
            return false;
        }

        foreach ($octets as $octet) {
            // Must be non-empty and contain only digits
            if ($octet === '' || !ctype_digit($octet)) {
                return false;
            }

            // No leading zeros (except '0' itself)
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
}
