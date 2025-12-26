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

/**
 * UUID format validator for JSON Schema.
 *
 * Validates that a value conforms to the UUID (Universally Unique Identifier) format
 * as defined in RFC 4122. UUIDs are 128-bit identifiers displayed as 36-character
 * hexadecimal strings in the canonical 8-4-4-4-12 format with hyphens as separators.
 *
 * Supported UUID versions:
 * - Version 1: Time-based UUID
 * - Version 2: DCE Security UUID
 * - Version 3: Name-based UUID (MD5 hash)
 * - Version 4: Random UUID
 * - Version 5: Name-based UUID (SHA-1 hash)
 *
 * Format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx (32 hex digits with 4 hyphens)
 * Example: 550e8400-e29b-41d4-a716-446655440000
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc4122 RFC 4122: A Universally Unique IDentifier (UUID) URN Namespace
 * @see https://datatracker.ietf.org/doc/html/rfc4122#section-3 RFC 4122 Section 3: UUID Format
 */
final readonly class UuidFormatValidator implements FormatValidatorInterface
{
    /**
     * UUID regex pattern for canonical format validation.
     *
     * Matches UUIDs in the standard 8-4-4-4-12 hexadecimal format with hyphens.
     * Case-insensitive to accept both uppercase and lowercase hex digits.
     */
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * Validate a value against the UUID format.
     *
     * Uses regex pattern matching to verify the UUID structure. Accepts UUIDs
     * with lowercase or uppercase hexadecimal digits in the canonical format
     * with hyphens at the standard positions (8-4-4-4-12).
     *
     * @param mixed $value The value to validate as a UUID
     *
     * @return bool True if the value is a valid UUID in canonical format, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match(self::UUID_PATTERN, $value) === 1;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'uuid'
     */
    public function format(): string
    {
        return 'uuid';
    }
}
