<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function floor;
use function is_string;
use function preg_match;

/**
 * Time format validator for JSON Schema.
 *
 * Validates that a value conforms to the time format as defined in RFC 3339 section 5.6,
 * which is a profile of ISO 8601. The validator performs comprehensive validation of
 * time components including leap second support and timezone offset validation.
 *
 * Accepted format: HH:MM:SS[.fraction](Z|±HH:MM) (full-time in RFC 3339)
 * Examples:
 * - 08:30:06Z
 * - 23:59:60Z (leap second at UTC midnight)
 * - 08:30:06.283185Z (with fractional seconds)
 * - 08:30:06+05:30 (with timezone offset)
 * - 08:30:06-08:00
 *
 * Rejected formats:
 * - Time without timezone (12:00:00)
 * - ISO 8601 comma separator (01:01:01,1111)
 * - Non-padded components (8:3:6Z, 8:0030:6Z)
 * - Invalid leap seconds (not at 23:59:60 UTC equivalent)
 * - Time with both Z and offset (01:02:03Z+00:30)
 * - Named timezones (08:30:06 PST)
 * - Non-ASCII digits
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#dates-and-times JSON Schema Time Format
 * @see https://datatracker.ietf.org/doc/html/rfc3339#section-5.6 RFC 3339 Section 5.6: Full Time
 */
final readonly class TimeFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the time format.
     *
     * Performs comprehensive validation including:
     * - Strict RFC 3339 format structure (HH:MM:SS with zero-padding)
     * - Valid hour ranges (00-23)
     * - Valid minute ranges (00-59)
     * - Valid second ranges (00-60, allowing leap seconds)
     * - Leap second validation (60 is only valid at 23:59:60 UTC)
     * - Timezone offset validation (Z or ±HH:MM format)
     * - Optional fractional seconds support
     * - Rejection of ISO 8601 formats not included in RFC 3339
     * - Rejection of non-ASCII digits
     *
     * @param mixed $value The value to validate as a time string
     *
     * @return bool True if the value is a valid RFC 3339 time string, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // RFC 3339 full-time format: HH:MM:SS[.fraction](Z|±HH:MM)
        // Allow case-insensitive Z
        $pattern = '/^'
            .'(\d{2}):(\d{2}):(\d{2})'   // time (HH:MM:SS)
            .'(\.\d+)?'                   // optional fractional seconds
            .'([Zz]|[+-]\d{2}:\d{2})'    // timezone (Z or ±HH:MM, case-insensitive)
            .'$/';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return false;
        }

        // Validate that we're using ASCII digits only (reject Bengali, Arabic, etc.)
        // Check the core time part and timezone offset
        $timePattern = '/^\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/';

        if (preg_match($timePattern, $value) !== 1) {
            return false;
        }

        [, $hour, $minute, $second, , $timezone] = $matches;

        $hour = (int) $hour;
        $minute = (int) $minute;
        $second = (int) $second;

        // Validate hour (0-23)
        if ($hour < 0 || $hour > 23) {
            return false;
        }

        // Validate minute (0-59)
        if ($minute < 0 || $minute > 59) {
            return false;
        }

        // Validate second (0-60, allowing leap seconds)
        if ($second < 0 || $second > 60) {
            return false;
        }

        // Leap seconds (second=60) are only valid at 23:59:60 UTC
        // We need to convert the local time to UTC to validate
        if ($second === 60) {
            $utcHour = $hour;
            $utcMinute = $minute;

            // Parse timezone offset
            if ($timezone !== 'Z' && $timezone !== 'z' && preg_match('/^([+-])(\d{2}):(\d{2})$/', $timezone, $tzParts)) {
                $sign = $tzParts[1];
                $offsetHours = (int) $tzParts[2];
                $offsetMinutes = (int) $tzParts[3];

                // Validate offset ranges
                if ($offsetHours < 0 || $offsetHours > 23) {
                    return false;
                }

                if ($offsetMinutes < 0 || $offsetMinutes > 59) {
                    return false;
                }

                // Convert offset to total minutes
                $totalOffsetMinutes = $offsetHours * 60 + $offsetMinutes;

                if ($sign === '-') {
                    $totalOffsetMinutes = -$totalOffsetMinutes;
                }

                // Convert local time to UTC
                // UTC = local - offset
                // e.g., 15:59-08:00 means UTC = 15:59 - (-8:00) = 15:59 + 8:00 = 23:59
                $totalMinutes = $utcHour * 60 + $utcMinute - $totalOffsetMinutes;
                // Handle day overflow/underflow
                $utcHour = (int) floor($totalMinutes / 60) % 24;

                if ($utcHour < 0) {
                    $utcHour += 24;
                }

                $utcMinute = $totalMinutes % 60;

                if ($utcMinute < 0) {
                    $utcMinute += 60;
                }
            }

            // Leap second is only valid at 23:59:60 UTC
            if ($utcHour !== 23 || $utcMinute !== 59) {
                return false;
            }
        }

        // Validate timezone offset if not Z or z
        if ($timezone !== 'Z' && $timezone !== 'z' && preg_match('/^([+-])(\d{2}):(\d{2})$/', $timezone, $tzParts)) {
            $offsetHours = (int) $tzParts[2];
            $offsetMinutes = (int) $tzParts[3];

            // Offset hours must be 0-23
            if ($offsetHours < 0 || $offsetHours > 23) {
                return false;
            }

            // Offset minutes must be 0-59
            if ($offsetMinutes < 0 || $offsetMinutes > 59) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'time'
     */
    public function format(): string
    {
        return 'time';
    }
}
