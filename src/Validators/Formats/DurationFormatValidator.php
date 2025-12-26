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
 * ISO 8601 duration format validator for JSON Schema.
 *
 * Validates that a value conforms to the ISO 8601 duration format as defined in
 * RFC 3339. Durations are represented as strings starting with 'P' followed by
 * date and time components.
 *
 * Format: P[n]Y[n]M[n]DT[n]H[n]M[n]S or P[n]W
 * - P: Required duration designator (period)
 * - Date part (optional): [n]Y (years), [n]M (months), [n]D (days)
 * - T: Time designator (required if time part exists)
 * - Time part (optional): [n]H (hours), [n]M (minutes), [n]S (seconds)
 * - Week format: [n]W (weeks, alternative to date/time parts)
 *
 * Valid examples: P3Y6M4DT12H30M5S, P23DT23H, P4Y, PT0S, P1W
 * Invalid examples: P, PT, P1Y2M3DT, P1.5Y
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://datatracker.ietf.org/doc/html/rfc3339#appendix-A RFC 3339 Appendix A: ISO 8601
 * @see https://en.wikipedia.org/wiki/ISO_8601#Durations ISO 8601 Duration Format
 */
final readonly class DurationFormatValidator implements FormatValidatorInterface
{
    /**
     * ISO 8601 duration regex pattern.
     *
     * Matches durations in the format P[n]Y[n]M[n]DT[n]H[n]M[n]S or P[n]W
     * - Supports integer values for all components
     * - Supports decimal seconds (e.g., PT0.5S)
     * - Requires at least one component after P
     * - Week format cannot be combined with other units
     */
    private const string DURATION_PATTERN = '/^P(?!$)(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?(?:T(?=\d)(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?)?$/';

    /**
     * Week-based duration pattern (alternative format).
     */
    private const string WEEK_PATTERN = '/^P\d+W$/';

    /**
     * Validate a value against the ISO 8601 duration format.
     *
     * Validates the duration string structure according to ISO 8601 rules:
     * - Must start with P
     * - Must have at least one date or time component
     * - T is required if time components are present
     * - Week format is separate and cannot mix with other components
     *
     * @param mixed $value The value to validate as an ISO 8601 duration
     *
     * @return bool True if the value is a valid ISO 8601 duration, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for week-based duration format
        if (preg_match(self::WEEK_PATTERN, $value) === 1) {
            return true;
        }

        // Check for standard duration format
        return preg_match(self::DURATION_PATTERN, $value) === 1;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'duration'
     */
    public function format(): string
    {
        return 'duration';
    }
}
