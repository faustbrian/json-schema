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
 * Date-time format validator for JSON Schema.
 *
 * Validates that a value conforms to the date-time format as defined in RFC 3339,
 * which is a profile of ISO 8601. The validator performs comprehensive validation
 * including date component ranges, leap year handling, leap second support, and
 * timezone offset validation.
 *
 * Accepted format: YYYY-MM-DDTHH:MM:SS[.fraction](Z|±HH:MM)
 * Examples:
 * - 2024-01-15T14:30:00Z
 * - 2024-01-15T14:30:00.123Z
 * - 2024-01-15T14:30:00+05:30
 * - 2024-12-31T23:59:60Z (leap second)
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#dates-and-times JSON Schema Date-Time Format
 * @see https://datatracker.ietf.org/doc/html/rfc3339 RFC 3339: Date and Time on the Internet
 * @see https://datatracker.ietf.org/doc/html/rfc3339#section-5.6 RFC 3339 Section 5.6: Date-Time Format
 */
final readonly class DateTimeFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the date-time format.
     *
     * Performs comprehensive validation including:
     * - Format structure matching RFC 3339 specification
     * - Valid date ranges (month 1-12, proper days per month)
     * - Leap year calculation for February 29th
     * - Time component ranges (hours 0-23, minutes 0-59, seconds 0-60)
     * - Leap second validation (60 is only valid at 23:59:60 UTC)
     * - Timezone offset validation (±HH:MM format with valid ranges)
     * - Optional fractional seconds support
     *
     * @param mixed $value The value to validate as a date-time string
     *
     * @return bool True if the value is a valid RFC 3339 date-time string, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // RFC 3339 date-time format with optional fractional seconds
        // Format: YYYY-MM-DDTHH:MM:SS[.fraction](Z|+HH:MM|-HH:MM)
        // Allow case-insensitive T and Z
        $pattern = '/^'
            .'(\d{4})-(\d{2})-(\d{2})'  // date (YYYY-MM-DD)
            .'[Tt]'                       // time separator (case-insensitive)
            .'(\d{2}):(\d{2}):(\d{2})'   // time (HH:MM:SS)
            .'(\.\d+)?'                   // optional fractional seconds
            .'([Zz]|[+-]\d{2}:\d{2})'    // timezone (Z or ±HH:MM, case-insensitive)
            .'$/';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return false;
        }

        // Validate that we're using ASCII digits only (reject Bengali, Arabic, etc.)
        // Also reject extended years (no leading + or -)
        $asciiPattern = '/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/';

        if (preg_match($asciiPattern, $value) !== 1) {
            return false;
        }

        // Validate date components
        [, $year, $month, $day, $hour, $minute, $second] = $matches;

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        $hour = (int) $hour;
        $minute = (int) $minute;
        $second = (int) $second;

        // Validate month (1-12)
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Validate day is valid for the given month/year
        $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Check for leap year
        if ($month === 2 && ($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0))) {
            $daysInMonth[1] = 29;
        }

        if ($day < 1 || $day > $daysInMonth[$month - 1]) {
            return false;
        }

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
        // Convert local time to UTC to check
        if ($second === 60) {
            $utcHour = $hour;
            $utcMinute = $minute;

            // Get timezone offset
            $timezone = $matches[8];

            if ($timezone !== 'Z' && $timezone !== 'z' && preg_match('/^([+-])(\d{2}):(\d{2})$/', $timezone, $tzParts)) {
                $sign = $tzParts[1];
                $offsetHours = (int) $tzParts[2];
                $offsetMinutes = (int) $tzParts[3];
                // Convert offset to minutes
                $totalOffsetMinutes = $offsetHours * 60 + $offsetMinutes;

                if ($sign === '-') {
                    $totalOffsetMinutes = -$totalOffsetMinutes;
                }

                // Add offset to get UTC time (local + offset = UTC for negative offsets)
                // e.g., 15:59 with offset -08:00 means UTC = 15:59 + 8:00 = 23:59
                $totalMinutes = $utcHour * 60 + $utcMinute - $totalOffsetMinutes;
                $utcHour = (int) floor($totalMinutes / 60) % 24;

                if ($utcHour < 0) {
                    $utcHour += 24;
                }

                $utcMinute = $totalMinutes % 60;

                if ($utcMinute < 0) {
                    $utcMinute += 60;
                }
            }

            // Check if it's 23:59:60 in UTC
            if ($utcHour !== 23 || $utcMinute !== 59) {
                return false;
            }
        }

        // Validate timezone offset if not Z or z
        $timezone = $matches[8];

        if ($timezone !== 'Z' && $timezone !== 'z') {
            $offset = $timezone;

            if (preg_match('/^([+-])(\d{2}):(\d{2})$/', $offset, $offsetParts)) {
                $offsetHour = (int) $offsetParts[2];
                $offsetMinute = (int) $offsetParts[3];

                // Offset hours must be 0-23
                if ($offsetHour < 0 || $offsetHour > 23) {
                    return false;
                }

                // Offset minutes must be 0-59
                if ($offsetMinute < 0 || $offsetMinute > 59) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'date-time'
     */
    public function format(): string
    {
        return 'date-time';
    }
}
