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
use function mb_substr_count;
use function str_contains;

/**
 * IRI Reference format validator for JSON Schema.
 *
 * Validates that a value conforms to the IRI reference format as defined in RFC 3987.
 * IRI references are a superset of IRIs, allowing both absolute IRIs (with schemes)
 * and relative references (without schemes), with Unicode support.
 *
 * Valid formats include:
 * - Absolute IRIs: https://example.com/path, http://例え.jp
 * - Relative paths: /path, ./path, ../path, path
 * - Query only: ?query=value
 * - Fragment only: #section, #фрагмент
 * - Empty string: "" (valid IRI reference)
 *
 * Validation rules:
 * - No backslashes (Windows-style paths are invalid)
 * - No unescaped special characters (<, >, ", {, }, |, ^, `)
 * - Brackets [ and ] must be balanced (for IPv6 addresses)
 * - Allows Unicode characters (unlike URI reference)
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc3987 RFC 3987: Internationalized Resource Identifiers (IRIs)
 * @see https://datatracker.ietf.org/doc/html/rfc3987#section-2.2 RFC 3987 Section 2.2: IRI References
 */
final readonly class IriReferenceFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the IRI reference format.
     *
     * Performs validation of both absolute IRIs and relative references according
     * to RFC 3987. Unlike the strict IRI validator, this accepts relative paths,
     * fragment-only references, and empty strings, making it suitable for general
     * web resource references with internationalization support.
     *
     * @param mixed $value The value to validate as an IRI reference
     *
     * @return bool True if the value is a valid IRI reference, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Empty string is a valid IRI reference
        if ($value === '') {
            return true;
        }

        // RFC 3987 disallows these characters unless percent-encoded
        // Note: [ and ] are allowed for IPv6 addresses in authority
        // Backslash is explicitly forbidden (Windows-style paths are invalid)
        $invalidChars = ['<', '>', '"', '{', '}', '|', '\\', '^', '`', ' '];

        foreach ($invalidChars as $char) {
            if (str_contains($value, $char)) {
                return false;
            }
        }

        // Validate that [ and ] are balanced (for IPv6 addresses)
        $openBrackets = mb_substr_count($value, '[');
        $closeBrackets = mb_substr_count($value, ']');

        // IRI references allow Unicode characters, so no ASCII-only check
        // Unlike URI references, we accept international characters
        return $openBrackets === $closeBrackets;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'iri-reference'
     */
    public function format(): string
    {
        return 'iri-reference';
    }
}
