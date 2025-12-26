<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use const IDNA_CHECK_BIDI;
use const IDNA_CHECK_CONTEXTJ;
use const IDNA_DEFAULT;
use const IDNA_USE_STD3_RULES;
use const INTL_IDNA_VARIANT_UTS46;

use function explode;
use function idn_to_ascii;
use function idn_to_utf8;
use function is_string;
use function mb_ord;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function str_ends_with;
use function str_replace;
use function str_starts_with;

/**
 * Internationalized Domain Name (IDN) hostname format validator for JSON Schema.
 *
 * Validates that a value conforms to the idn-hostname format as defined in RFC 5890.
 * IDN hostnames can contain Unicode characters (U-labels) which are validated according
 * to IDNA2008 rules via the PHP intl extension.
 *
 * Validation rules:
 * - Maximum 253 characters total length (when encoded as ASCII)
 * - Labels (segments) separated by dots (or Unicode dot variants)
 * - Each label must be 1-63 characters (when encoded as ASCII)
 * - Labels must not start or end with hyphen
 * - Unicode characters must conform to IDNA2008 allowed character sets
 * - Labels cannot begin with combining marks
 *
 * Valid examples: 실례.테스트, example.com, münchen.de
 * Invalid examples: -example, 〮실례.테스트, l·
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#hostnames JSON Schema Hostname Format
 * @see https://datatracker.ietf.org/doc/html/rfc5890 RFC 5890: Internationalized Domain Names for Applications (IDNA)
 * @see https://datatracker.ietf.org/doc/html/rfc5891 RFC 5891: Internationalized Domain Names in Applications (IDNA): Protocol
 */
final readonly class IdnHostnameFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the idn-hostname format.
     *
     * Performs comprehensive IDN hostname validation including Unicode character
     * validation, length checks, label validation, and IDNA2008 compliance.
     *
     * @param mixed $value The value to validate as an IDN hostname
     *
     * @return bool True if the value is a valid IDN hostname, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Hostname must not be empty
        if ($value === '') {
            return false;
        }

        // Normalize Unicode dot separators to ASCII dot
        // U+3002 (ideographic full stop), U+FF0E (fullwidth full stop), U+FF61 (halfwidth ideographic full stop)
        $normalized = str_replace(["\u{3002}", "\u{FF0E}", "\u{FF61}"], '.', $value);

        // Single dot is invalid
        if ($normalized === '.') {
            return false;
        }

        // Hostname must not start or end with dot
        if (str_starts_with($normalized, '.') || str_ends_with($normalized, '.')) {
            return false;
        }

        // Split into labels
        $labels = explode('.', $normalized);

        foreach ($labels as $label) {
            // Label must not be empty
            if ($label === '') {
                return false;
            }

            // Label must not start or end with hyphen
            if (str_starts_with($label, '-') || str_ends_with($label, '-')) {
                return false;
            }

            // Check for "--" in positions 3 and 4 (except for valid xn-- punycode)
            // Allow xn-- prefix for punycode, reject others
            if (mb_strlen($label) >= 4 && mb_substr($label, 2, 2) === '--' && !str_starts_with(mb_strtolower($label), 'xn--')) {
                return false;
            }

            // Validate the label using IDNA
            if (!$this->validateIdnLabel($label)) {
                return false;
            }
        }

        // Check total length when encoded as ASCII (max 253 characters)
        $info = [];
        $ascii = idn_to_ascii(
            $normalized,
            IDNA_DEFAULT | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ,
            INTL_IDNA_VARIANT_UTS46,
            $info,
        );

        // If conversion fails or has errors, it's invalid
        // @phpstan-ignore-next-line - $info is array from idn_to_ascii IDNA_INFO parameter
        if ($ascii === false || ($info['errors'] ?? 0) !== 0) {
            return false;
        }

        // Check total ASCII length
        return mb_strlen($ascii) <= 253;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'idn-hostname'
     */
    public function format(): string
    {
        return 'idn-hostname';
    }

    /**
     * Validate an individual IDN label.
     *
     * Checks if a label (U-label or A-label) is valid according to IDNA2008.
     *
     * @param string $label The label to validate
     *
     * @return bool True if the label is valid, false otherwise
     */
    private function validateIdnLabel(string $label): bool
    {
        // If label is punycode (xn--*), decode it first
        $decoded = $label;

        if (str_starts_with(mb_strtolower($label), 'xn--')) {
            $decoded = idn_to_utf8($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($decoded === false) {
                return false;
            }
        }

        // Apply IDNA2008 contextual rules to decoded label
        if (!$this->validateContextualRules($decoded)) {
            return false;
        }

        // Convert label to ASCII (A-label) to check validity
        $info = [];
        $ascii = idn_to_ascii(
            $label,
            IDNA_DEFAULT | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ,
            INTL_IDNA_VARIANT_UTS46,
            $info,
        );

        // If conversion fails or has errors, it's invalid
        // @phpstan-ignore-next-line - $info is array from idn_to_ascii IDNA_INFO parameter
        if ($ascii === false || ($info['errors'] ?? 0) !== 0) {
            return false;
        }

        // Check ASCII label length (max 63 characters)
        return mb_strlen($ascii) <= 63;
    }

    /**
     * Validate IDNA2008 contextual rules for a label.
     *
     * Implements CONTEXTJ and CONTEXTO rules from RFC 5892.
     *
     * @param string $label The Unicode label to validate
     *
     * @return bool True if all contextual rules pass, false otherwise
     */
    private function validateContextualRules(string $label): bool
    {
        $length = mb_strlen($label);

        for ($i = 0; $i < $length; ++$i) {
            $char = mb_substr($label, $i, 1);
            $codepoint = mb_ord($char);

            // MIDDLE DOT (U+00B7): Must be between two lowercase 'l' characters
            if ($codepoint === 0x00_B7) {
                $prevChar = $i > 0 ? mb_substr($label, $i - 1, 1) : '';
                $nextChar = $i < $length - 1 ? mb_substr($label, $i + 1, 1) : '';

                if ($prevChar !== 'l' || $nextChar !== 'l') {
                    return false;
                }
            }

            // GREEK LOWER NUMERAL SIGN (KERAIA, U+0375): Must be followed by Greek character
            if ($codepoint === 0x03_75) {
                if ($i >= $length - 1) {
                    return false; // Must have a following character
                }

                $nextChar = mb_substr($label, $i + 1, 1);
                $nextCodepoint = mb_ord($nextChar);

                // Greek range: U+0370-U+03FF (Greek and Coptic)
                if ($nextCodepoint < 0x03_70 || $nextCodepoint > 0x03_FF) {
                    return false;
                }
            }

            // HEBREW PUNCTUATION GERESH (U+05F3) and GERSHAYIM (U+05F4):
            // Must be preceded by Hebrew character
            if ($codepoint === 0x05_F3 || $codepoint === 0x05_F4) {
                if ($i === 0) {
                    return false; // Must have a preceding character
                }

                $prevChar = mb_substr($label, $i - 1, 1);
                $prevCodepoint = mb_ord($prevChar);

                // Hebrew range: U+0590-U+05FF
                if ($prevCodepoint < 0x05_90 || $prevCodepoint > 0x05_FF) {
                    return false;
                }
            }

            // Hangul/CJK combining marks (U+302E, U+302F): Context rules
            // RFC 5892: These can only appear after Hangul Jamo, NOT Hangul Syllables
            if ($codepoint === 0x30_2E || $codepoint === 0x30_2F) {
                // Cannot appear at position 0
                if ($i === 0) {
                    return false;
                }

                // Must be preceded by Hangul Jamo (NOT Hangul Syllables)
                $prevChar = mb_substr($label, $i - 1, 1);
                $prevCodepoint = mb_ord($prevChar);

                $isHangulJamo = ($prevCodepoint >= 0x11_00 && $prevCodepoint <= 0x11_FF)  // Hangul Jamo
                                || ($prevCodepoint >= 0x31_30 && $prevCodepoint <= 0x31_8F);    // Hangul Compatibility Jamo

                if (!$isHangulJamo) {
                    return false;
                }
            }

            // KATAKANA MIDDLE DOT (U+30FB): Must appear with Hiragana, Katakana, or Han characters
            if ($codepoint === 0x30_FB) {
                $hasJapanese = false;

                // Check all characters in label for Japanese scripts
                for ($j = 0; $j < $length; ++$j) {
                    if ($j === $i) {
                        continue; // Skip the middle dot itself
                    }

                    $checkChar = mb_substr($label, $j, 1);
                    $checkCodepoint = mb_ord($checkChar);

                    // Hiragana: U+3040-U+309F, Katakana: U+30A0-U+30FF, Han: U+4E00-U+9FFF
                    if (($checkCodepoint >= 0x30_40 && $checkCodepoint <= 0x30_9F)  // Hiragana
                        || ($checkCodepoint >= 0x30_A0 && $checkCodepoint <= 0x30_FF && $checkCodepoint !== 0x30_FB)  // Katakana (excluding middle dot itself)
                        || ($checkCodepoint >= 0x4E_00 && $checkCodepoint <= 0x9F_FF)) {   // CJK Unified Ideographs
                        $hasJapanese = true;

                        break;
                    }
                }

                if (!$hasJapanese) {
                    return false;
                }
            }

            // Arabic-Indic and Extended Arabic-Indic digits cannot be mixed
            // Arabic-Indic: U+0660-U+0669, Extended Arabic-Indic: U+06F0-U+06F9
            if (($codepoint >= 0x06_60 && $codepoint <= 0x06_69) || ($codepoint >= 0x06_F0 && $codepoint <= 0x06_F9)) {
                $hasArabicIndic = false;
                $hasExtendedArabicIndic = false;

                for ($j = 0; $j < $length; ++$j) {
                    $checkChar = mb_substr($label, $j, 1);
                    $checkCodepoint = mb_ord($checkChar);

                    if ($checkCodepoint >= 0x06_60 && $checkCodepoint <= 0x06_69) {
                        $hasArabicIndic = true;
                    }

                    if ($checkCodepoint < 0x06_F0) {
                        continue;
                    }

                    if ($checkCodepoint > 0x06_F9) {
                        continue;
                    }

                    $hasExtendedArabicIndic = true;
                }

                // Cannot mix both types
                if ($hasArabicIndic && $hasExtendedArabicIndic) {
                    return false;
                }
            }

            // ARABIC TATWEEL (U+0640): Disallowed in IDNA2008
            if ($codepoint === 0x06_40) {
                return false;
            }

            // NKO LAJANYALAN (U+07FA): Disallowed
            if ($codepoint === 0x07_FA) {
                return false;
            }

            // Various CJK marks that are disallowed (U+3031-U+3035, U+303B)
            if (($codepoint >= 0x30_31 && $codepoint <= 0x30_35) || $codepoint === 0x30_3B) {
                return false;
            }
        }

        return true;
    }
}
