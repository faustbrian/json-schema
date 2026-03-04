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
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function preg_match;
use function str_ends_with;
use function str_starts_with;

/**
 * Hostname format validator for JSON Schema.
 *
 * Validates that a value conforms to the hostname format as defined in RFC 1123
 * (Requirements for Internet Hosts). Hostnames are used in DNS and must follow
 * specific structural and character constraints including label length limits,
 * allowed characters, and overall hostname length restrictions.
 *
 * Validation rules:
 * - Maximum 253 characters total length
 * - Labels (segments) separated by dots
 * - Each label must be 1-63 characters
 * - Labels must start and end with alphanumeric characters
 * - Labels may contain hyphens in the middle
 * - Labels must contain only alphanumeric characters and hyphens
 *
 * Valid examples: example.com, sub.example.com, api-v2.example.com
 * Invalid examples: -example.com, example-.com, example..com
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#hostnames JSON Schema Hostname Format
 * @see https://datatracker.ietf.org/doc/html/rfc1123 RFC 1123: Requirements for Internet Hosts
 * @see https://datatracker.ietf.org/doc/html/rfc1123#section-2.1 RFC 1123 Section 2.1: Host Names and Numbers
 * @see https://datatracker.ietf.org/doc/html/rfc1035 RFC 1035: Domain Names - Implementation and Specification
 */
final readonly class HostnameFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the hostname format.
     *
     * Performs comprehensive hostname validation including length checks,
     * label validation, and character constraints according to RFC 1123.
     *
     * @param mixed $value The value to validate as a hostname
     *
     * @return bool True if the value is a valid RFC 1123 hostname, false otherwise
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

        // Single dot is invalid
        if ($value === '.') {
            return false;
        }

        // Check for IDN label separators (U+FF0E fullwidth full stop)
        if (mb_strpos($value, "\u{FF0E}") !== false) {
            return false;
        }

        // Hostname must not exceed 253 characters
        if (mb_strlen($value) > 253) {
            return false;
        }

        // Hostname must not start or end with dot
        if (str_starts_with($value, '.') || str_ends_with($value, '.')) {
            return false;
        }

        // Split into labels
        $labels = explode('.', $value);

        foreach ($labels as $label) {
            // Label must not be empty
            if ($label === '') {
                return false;
            }

            // Label must not exceed 63 characters
            if (mb_strlen($label) > 63) {
                return false;
            }

            // Label must not start or end with hyphen
            if (str_starts_with($label, '-') || str_ends_with($label, '-')) {
                return false;
            }

            // Label must contain only alphanumeric characters and hyphens (no underscores)
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $label)) {
                return false;
            }

            // Check for "--" in positions 3 and 4
            // This is only invalid if it's NOT a valid xn-- punycode label
            if (mb_strlen($label) >= 4 && mb_substr($label, 2, 2) === '--') {
                $isXnLabel = str_starts_with(mb_strtolower($label), 'xn--');

                if (!$isXnLabel) {
                    // Not a punycode label but has -- in positions 3-4, invalid
                    return false;
                }

                // It's a punycode label, validate it properly
                if (!$this->validatePunycode($label)) {
                    return false;
                }
            } elseif (str_starts_with(mb_strtolower($label), 'xn--')) {
                // It's a punycode label without enough characters
                if (!$this->validatePunycode($label)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'hostname'
     */
    public function format(): string
    {
        return 'hostname';
    }

    /**
     * Validate a punycode label.
     *
     * Performs IDNA validation of punycode encoded labels (A-labels).
     * Uses UTS46 variant with strict IDNA2008 validation including
     * CONTEXTJ, BIDI, and STD3 rules.
     *
     * @param string $label The label to validate
     *
     * @return bool True if the label is valid punycode, false otherwise
     */
    private function validatePunycode(string $label): bool
    {
        // Punycode labels must be at least 5 characters (xn-- + at least one char)
        if (mb_strlen($label) < 5) {
            return false;
        }

        // First decode to get the Unicode representation
        $decoded = idn_to_utf8($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        if ($decoded === false) {
            return false;
        }

        // Now re-encode with strict IDNA2008 validation flags to check validity
        // This validates CONTEXTJ characters (ZWJ/ZWNJ), BIDI rules, and STD3 rules
        $info = [];
        $reencoded = idn_to_ascii(
            $decoded,
            IDNA_DEFAULT | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ,
            INTL_IDNA_VARIANT_UTS46,
            $info,
        );

        // Check if there were any errors during encoding
        // @phpstan-ignore-next-line - $info is array from idn_to_utf8 IDNA_INFO parameter
        if ($reencoded === false || ($info['errors'] ?? 0) !== 0) {
            return false;
        }

        // Apply IDNA2008 contextual rules to decoded label
        return $this->validateContextualRules($decoded);
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
