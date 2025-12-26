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
use function preg_quote;
use function restore_error_handler;
use function set_error_handler;

/**
 * ECMAScript regular expression format validator for JSON Schema.
 *
 * Validates that a value is a valid ECMAScript (JavaScript) regular expression.
 * This validator checks for ECMA-262 compliance including:
 * - Balanced parentheses and brackets
 * - Valid ECMA-262 escape sequences
 * - Proper group structures
 * - Rejection of invalid control escapes
 *
 * The validator ensures patterns conform to ECMA-262 by checking for invalid
 * escape sequences like \a, \g, \h, \i, \j, \k, \l, \m, \o, \p, \q, \y, \z, \A,
 * \B (outside character class), \C, \D, \E, \F, \G, \H, \I, \J, \K, \L, \M,
 * \N, \O, \P, \Q, \R, \Y, \Z which are not valid in ECMA-262.
 *
 * Valid examples: ^test$, [a-z]+, (abc|def), \d{3}, \n, \t
 * Invalid examples: ^(abc], [a-z, (unclosed, \a, \g
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://www.ecma-international.org/ecma-262/ ECMAScript Language Specification
 * @see https://262.ecma-international.org/5.1/#sec-15.10.2.10 ECMA-262 Character Escapes
 */
final readonly class RegexFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the ECMAScript regex format.
     *
     * Validates ECMA-262 compliance by checking for invalid escape sequences
     * and attempting to compile the pattern. Invalid patterns such as unclosed
     * groups, unbalanced brackets, or illegal escape sequences will be rejected.
     *
     * ECMA-262 valid control escapes: \f \n \r \t \v
     * ECMA-262 valid character class escapes: \d \D \s \S \w \W
     * ECMA-262 valid assertion escapes: \b \B (in specific contexts)
     *
     * @param mixed $value The value to validate as a regular expression
     *
     * @return bool True if the value is a valid ECMAScript regex, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for invalid ECMA-262 escape sequences
        // Valid single-letter escapes: b, c, d, D, f, n, r, s, S, t, u, v, w, W, x
        // Invalid escapes include: \a, \e, \g, \h, \i, \j, \k, \l, \m, \o, \p, \q, \y, \z
        // and uppercase versions that aren't already valid
        $invalidEscapes = [
            'a', 'e', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'o', 'p', 'q', 'y', 'z',
            'A', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'Y', 'Z',
        ];

        foreach ($invalidEscapes as $char) {
            // Check for the escape not preceded by another backslash
            // Use negative lookbehind to ensure it's not already escaped
            if (preg_match('/(?<!\\\\)\\\\'.preg_quote($char, '/').'/', $value)) {
                return false;
            }
        }

        // Try to compile the regex pattern to check for syntax errors
        // Use error handler to suppress warnings from invalid patterns
        set_error_handler(static fn (): true => true);

        try {
            $result = preg_match('/'.$value.'/', '');
        } finally {
            restore_error_handler();
        }

        // preg_match returns false on error (invalid regex)
        // Returns 0 or 1 on success (valid regex)
        return $result !== false;
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'regex'
     */
    public function format(): string
    {
        return 'regex';
    }
}
