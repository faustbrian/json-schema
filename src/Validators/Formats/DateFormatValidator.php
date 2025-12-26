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
 * Date format validator for JSON Schema.
 *
 * Validates that a value conforms to the date format as defined in RFC 3339 section 5.6,
 * which is a profile of ISO 8601. The validator performs comprehensive validation of
 * date components including leap year handling and proper days per month validation.
 *
 * Accepted format: YYYY-MM-DD (full-date in RFC 3339)
 * Examples:
 * - 1963-06-19
 * - 2020-02-29 (leap year)
 * - 2024-12-31
 *
 * Rejected formats:
 * - ISO 8601 ordinal dates (2013-350)
 * - ISO 8601 week dates (2023-W01, 2023-W13-2)
 * - Dates without padding (1998-1-20, 1998-01-1)
 * - Dates with time components (2020-11-28T23:55:45Z)
 * - Invalid calendar dates (Feb 30, month 13, etc.)
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#dates-and-times JSON Schema Date Format
 * @see https://datatracker.ietf.org/doc/html/rfc3339#section-5.6 RFC 3339 Section 5.6: Full Date
 */
final readonly class DateFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the date format.
     *
     * Performs comprehensive validation including:
     * - Strict RFC 3339 format structure (YYYY-MM-DD with zero-padding)
     * - Valid month ranges (01-12)
     * - Valid day ranges based on the specific month
     * - Leap year calculation for February 29th
     * - Rejection of ISO 8601 formats not included in RFC 3339
     * - Rejection of non-ASCII digits
     *
     * @param mixed $value The value to validate as a date string
     *
     * @return bool True if the value is a valid RFC 3339 date string, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // RFC 3339 full-date format: YYYY-MM-DD
        // Must use ASCII digits only, with zero-padding
        $pattern = '/^(\d{4})-(\d{2})-(\d{2})$/';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return false;
        }

        // Validate that we're using ASCII digits (reject Bengali, Arabic, etc.)
        // Check the original string contains only allowed characters
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [, $year, $month, $day] = $matches;

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;

        // Validate month (1-12)
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Days per month (non-leap year)
        $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Check for leap year
        // A year is a leap year if:
        // - divisible by 4 AND (not divisible by 100 OR divisible by 400)
        if ($month === 2 && ($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0))) {
            $daysInMonth[1] = 29;
        }

        // Validate day is valid for the given month/year
        return $day >= 1 && $day <= $daysInMonth[$month - 1];
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'date'
     */
    public function format(): string
    {
        return 'date';
    }
}
