<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

use Cline\JsonSchema\Validators\Formats\DateFormatValidator;
use Cline\JsonSchema\Validators\Formats\DateTimeFormatValidator;
use Cline\JsonSchema\Validators\Formats\DurationFormatValidator;
use Cline\JsonSchema\Validators\Formats\EmailFormatValidator;
use Cline\JsonSchema\Validators\Formats\HostnameFormatValidator;
use Cline\JsonSchema\Validators\Formats\IdnEmailFormatValidator;
use Cline\JsonSchema\Validators\Formats\IdnHostnameFormatValidator;
use Cline\JsonSchema\Validators\Formats\Ipv4FormatValidator;
use Cline\JsonSchema\Validators\Formats\Ipv6FormatValidator;
use Cline\JsonSchema\Validators\Formats\IriFormatValidator;
use Cline\JsonSchema\Validators\Formats\IriReferenceFormatValidator;
use Cline\JsonSchema\Validators\Formats\JsonPointerFormatValidator;
use Cline\JsonSchema\Validators\Formats\RegexFormatValidator;
use Cline\JsonSchema\Validators\Formats\RelativeJsonPointerFormatValidator;
use Cline\JsonSchema\Validators\Formats\TimeFormatValidator;
use Cline\JsonSchema\Validators\Formats\UriFormatValidator;
use Cline\JsonSchema\Validators\Formats\UriReferenceFormatValidator;
use Cline\JsonSchema\Validators\Formats\UriTemplateFormatValidator;
use Cline\JsonSchema\Validators\Formats\UuidFormatValidator;

use const JSON_ERROR_NONE;

use function array_keys;
use function array_values;
use function assert;
use function base64_decode;
use function is_string;
use function json_decode;
use function json_last_error;
use function mb_strlen;
use function preg_match;
use function str_replace;

/**
 * String validation support for JSON Schema.
 *
 * Implements string constraint validation including length bounds and pattern matching.
 * Handles ECMA 262 to PCRE regex translation for cross-platform compatibility, UTF-8
 * mode detection, and format validation (email, URI, date-time, etc.) with proper
 * distinction between format-annotation and format-assertion vocabularies.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/string Understanding JSON Schema - Strings
 * @see https://json-schema.org/draft-04/json-schema-validation#rfc.section.5.2 Draft-04 - String Validation
 * @see https://json-schema.org/draft-06/json-schema-validation#rfc.section.6.3 Draft-06 - String Validation
 * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.3 Draft-07 - String Validation
 * @see https://json-schema.org/draft/2019-09/json-schema-validation#rfc.section.6.3 Draft 2019-09 - String Validation
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-str Draft 2020-12 - String Validation
 */
trait ValidatesStrings
{
    /**
     * Validate the minLength keyword (minimum string length).
     *
     * Validates that string values meet or exceed the specified minimum character count.
     * Uses multibyte-safe strlen to correctly count Unicode characters. Only applies
     * to string types; other types pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length Understanding JSON Schema - String Length
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-minlength Draft 2020-12 - minLength
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the minLength constraint
     * @return bool                 True if the string length meets the minimum requirement, false otherwise
     */
    protected function validateMinLength(mixed $data, array $schema): bool
    {
        if (!isset($schema['minLength']) || !is_string($data)) {
            return true;
        }

        return mb_strlen($data) >= $schema['minLength'];
    }

    /**
     * Validate the maxLength keyword (maximum string length).
     *
     * Validates that string values do not exceed the specified maximum character count.
     * Uses multibyte-safe strlen to correctly count Unicode characters. Only applies
     * to string types; other types pass automatically.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the maxLength constraint
     * @return bool                 True if the string length does not exceed the maximum, false otherwise
     */
    protected function validateMaxLength(mixed $data, array $schema): bool
    {
        if (!isset($schema['maxLength']) || !is_string($data)) {
            return true;
        }

        return mb_strlen($data) <= $schema['maxLength'];
    }

    /**
     * Validate the pattern keyword (regular expression matching).
     *
     * Validates that string values match the specified ECMA 262 regular expression.
     * Translates ECMA 262 Unicode properties and character classes to PCRE equivalents
     * for PHP compatibility. Automatically enables UTF-8 mode when the pattern contains
     * Unicode characters, property escapes, or ECMA 262 character classes.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#regular-expressions Understanding JSON Schema - Regular Expressions
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-pattern Draft 2020-12 - pattern
     * @see https://www.ecma-international.org/ecma-262/11.0/#sec-regexp-regular-expression-objects ECMA-262 Regular Expressions
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the pattern constraint
     * @return bool                 True if the string matches the pattern, false otherwise
     */
    protected function validatePattern(mixed $data, array $schema): bool
    {
        if (!isset($schema['pattern']) || !is_string($data)) {
            return true;
        }

        // Use 'u' modifier for patterns that need UTF-8 support:
        // 1. Patterns with non-ASCII characters (emoji, etc.)
        // 2. Patterns with Unicode property escapes (\p{...}, \P{...})
        // 3. ECMA 262 character classes that we translate (need UTF-8 to match multi-byte data)
        $pattern = $schema['pattern'];
        assert(is_string($pattern));

        // Check if UTF-8 mode needed BEFORE translation (ECMA classes need it)
        $needsUtf8 = $this->needsUtf8Mode($pattern);

        // Translate ECMA 262 Unicode property names to PCRE equivalents
        $pattern = $this->translateUnicodeProperties($pattern);

        $modifier = $needsUtf8 ? 'u' : '';

        return preg_match('/'.$pattern.'/'.$modifier, $data) === 1;
    }

    /**
     * Validate the format keyword (semantic format validation).
     *
     * Validates string values against semantic formats like date-time, email, URI, etc.
     * In Draft 2020-12+, distinguishes between format-annotation vocabulary (formats
     * are informational only) and format-assertion vocabulary (formats are validated).
     * Unknown format names are silently ignored per JSON Schema specification.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#format
     * @see https://json-schema.org/draft/2020-12/json-schema-validation#name-defined-formats
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the format constraint
     * @return bool                 True if the string matches the format or format validation is disabled, false otherwise
     */
    protected function validateFormat(mixed $data, array $schema): bool
    {
        if (!isset($schema['format']) || !is_string($data) || !is_string($schema['format'])) {
            return true;
        }

        // Note: Draft 2019-09 and 2020-12 validators override this method to check
        // whether format should be annotation-only or validated based on active vocabularies.
        // Earlier drafts (04, 06, 07) always validate format.

        return $this->performFormatValidation($data, $schema['format']);
    }

    /**
     * Perform actual format validation for a given format name.
     *
     * This helper method contains the actual format validation logic and can be
     * called by both the default validateFormat method and overridden versions
     * in specific draft validators.
     *
     * @param string $data       The string data to validate
     * @param string $formatName The format name (e.g., 'email', 'date-time')
     *
     * @return bool True if the string matches the format, false otherwise
     */
    protected function performFormatValidation(string $data, string $formatName): bool
    {
        $formatValidator = match ($formatName) {
            'date' => new DateFormatValidator(),
            'date-time' => new DateTimeFormatValidator(),
            'duration' => new DurationFormatValidator(),
            'email' => new EmailFormatValidator(),
            'hostname' => new HostnameFormatValidator(),
            'idn-email' => new IdnEmailFormatValidator(),
            'idn-hostname' => new IdnHostnameFormatValidator(),
            'ipv4' => new Ipv4FormatValidator(),
            'ipv6' => new Ipv6FormatValidator(),
            'iri' => new IriFormatValidator(),
            'iri-reference' => new IriReferenceFormatValidator(),
            'json-pointer' => new JsonPointerFormatValidator(),
            'regex' => new RegexFormatValidator(),
            'relative-json-pointer' => new RelativeJsonPointerFormatValidator(),
            'time' => new TimeFormatValidator(),
            'uri' => new UriFormatValidator(),
            'uri-reference' => new UriReferenceFormatValidator(),
            'uri-template' => new UriTemplateFormatValidator(),
            'uuid' => new UuidFormatValidator(),
            default => null,
        };

        if ($formatValidator === null) {
            // Unknown formats are ignored per JSON Schema spec
            return true;
        }

        return $formatValidator->validate($data);
    }

    /**
     * Validate the contentEncoding keyword (Draft 07+).
     *
     * Validates that string values are properly encoded according to the specified encoding.
     * Currently supports base64 encoding validation. Only applies to string types;
     * other types pass automatically.
     *
     * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.8.3 Draft-07 contentEncoding
     * @see https://json-schema.org/understanding-json-schema/reference/non_json_data#contentencoding Understanding JSON Schema - Content Encoding
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the contentEncoding constraint
     * @return bool                 True if the string is properly encoded, false otherwise
     */
    protected function validateContentEncoding(mixed $data, array $schema): bool
    {
        if (!isset($schema['contentEncoding']) || !is_string($data)) {
            return true;
        }

        $encoding = $schema['contentEncoding'];

        // Only validate base64 encoding for now (most common case)
        if ($encoding === 'base64') {
            // base64_decode with strict mode validates the encoding
            // Valid base64 uses only: A-Z, a-z, 0-9, +, /, and = for padding
            return base64_decode($data, true) !== false;
        }

        // Unknown encodings are not validated (per spec, contentEncoding is optional)
        return true;
    }

    /**
     * Validate the contentMediaType keyword (Draft 07+).
     *
     * Validates that string values contain valid content according to the specified media type.
     * When combined with contentEncoding, the encoding is decoded first, then the media type
     * is validated. Currently supports application/json validation. Only applies to string types;
     * other types pass automatically.
     *
     * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.8.4 Draft-07 contentMediaType
     * @see https://json-schema.org/understanding-json-schema/reference/non_json_data#contentmediatype Understanding JSON Schema - Content Media Type
     * @param  mixed                $data   The instance to validate (must be string for constraint to apply)
     * @param  array<string, mixed> $schema The schema definition containing the contentMediaType constraint
     * @return bool                 True if the string contains valid content for the media type, false otherwise
     */
    protected function validateContentMediaType(mixed $data, array $schema): bool
    {
        if (!isset($schema['contentMediaType']) || !is_string($data)) {
            return true;
        }

        $mediaType = $schema['contentMediaType'];
        $content = $data;

        // If contentEncoding is present, decode first
        if (isset($schema['contentEncoding'])) {
            $encoding = $schema['contentEncoding'];

            if ($encoding === 'base64') {
                $decoded = base64_decode($content, true);

                // If encoding is invalid, fail validation
                if ($decoded === false) {
                    return false;
                }

                $content = $decoded;
            }
        }

        // Validate based on media type
        if ($mediaType === 'application/json') {
            // Attempt to decode the JSON
            json_decode($content);

            // Check if there was an error
            return json_last_error() === JSON_ERROR_NONE;
        }

        // Unknown media types are not validated (per spec, contentMediaType is optional)
        return true;
    }

    /**
     * Translate ECMA 262 regex patterns to PCRE-compatible equivalents.
     *
     * Converts ECMA 262 Unicode property names (e.g., \p{Letter}) and character
     * classes (\d, \w, \s) to PCRE equivalents for use in PHP's preg_match.
     * Ensures ECMA 262 semantics are preserved, including ASCII-only character
     * classes and Unicode-aware whitespace handling.
     *
     * @param string $pattern The ECMA 262 regex pattern to translate
     *
     * @return string The PCRE-compatible regex pattern
     */
    private function translateUnicodeProperties(string $pattern): string
    {
        // ECMA 262 -> PCRE mappings for Unicode properties
        $translations = [
            '\\p{Letter}' => '\\p{L}',
            '\\P{Letter}' => '\\P{L}',
            '\\p{digit}' => '\\p{Nd}',
            '\\P{digit}' => '\\P{Nd}',
        ];

        // ECMA 262 character classes (must be ASCII-only per spec)
        // \s includes: tab, LF, VT, FF, CR, space, NBSP, ZWNBSP, LS, PS, and Zs category
        // \d is [0-9] only, \w is [a-zA-Z0-9_] only
        $ecmaClasses = [
            '\\d' => '[0-9]',
            '\\D' => '[^0-9]',
            '\\w' => '[a-zA-Z0-9_]',
            '\\W' => '[^a-zA-Z0-9_]',
            '\\s' => '[\x{0009}\x{000A}\x{000B}\x{000C}\x{000D}\x{0020}\x{00A0}\x{FEFF}\x{2028}\x{2029}\p{Zs}]',
            '\\S' => '[^\x{0009}\x{000A}\x{000B}\x{000C}\x{000D}\x{0020}\x{00A0}\x{FEFF}\x{2028}\x{2029}\p{Zs}]',
        ];

        $pattern = str_replace(array_keys($translations), array_values($translations), $pattern);

        return str_replace(array_keys($ecmaClasses), array_values($ecmaClasses), $pattern);
    }

    /**
     * Determine if a regex pattern requires UTF-8 mode for correct matching.
     *
     * Detects patterns that need the 'u' modifier in PCRE: non-ASCII characters,
     * Unicode property escapes, Unicode hex escapes, and ECMA 262 character classes
     * that must match multi-byte UTF-8 data correctly. This check is performed
     * before translation to preserve the intent of the original pattern.
     *
     * @param string $pattern The ECMA 262 regex pattern to analyze (before translation)
     *
     * @return bool True if the pattern requires UTF-8 mode, false for ASCII-safe patterns
     */
    private function needsUtf8Mode(string $pattern): bool
    {
        // Non-ASCII characters need UTF-8 mode
        if (preg_match('/[^\x00-\x7F]/', $pattern) === 1) {
            return true;
        }

        // Unicode property escapes (\p{...}, \P{...}) need UTF-8 mode
        if (preg_match('/\\\\[pP]\{/', $pattern) === 1) {
            return true;
        }

        // Unicode hex escapes (\x{...}) need UTF-8 mode
        if (preg_match('/\\\\x\{/', $pattern) === 1) {
            return true;
        }

        // ECMA 262 character classes need UTF-8 mode to match multi-byte UTF-8 data
        // Check for: \d, \D, \w, \W, \s, \S
        return preg_match('/\\\\[dDwWsS]/', $pattern) === 1;
    }
}
