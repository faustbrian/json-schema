<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Formats;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function array_all;
use function is_string;
use function mb_substr_count;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_contains;

/**
 * URI Template format validator for JSON Schema.
 *
 * Validates that a value conforms to the URI Template format as defined in RFC 6570.
 * URI Templates provide a way to specify URI patterns with variable placeholders
 * using curly brace syntax {variable}, enabling dynamic URI construction from
 * template strings and variable values.
 *
 * Format rules:
 * - Uses {variable} syntax for variable substitution
 * - Curly braces must be balanced
 * - Template expressions cannot be empty {}
 * - Variable names may contain alphanumeric characters, underscores, dots, and percent-encoding
 * - Supports operators for advanced expansion: {+var}, {#var}, {.var}, {/var}, etc.
 *
 * Valid examples:
 * - Simple: https://example.com/users/{id}
 * - Multiple: /api/{version}/users/{userId}
 * - With operators: /search{?query,page}
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format JSON Schema String Formats
 * @see https://json-schema.org/understanding-json-schema/reference/string#resource-identifiers JSON Schema Resource Identifiers
 * @see https://datatracker.ietf.org/doc/html/rfc6570 RFC 6570: URI Template
 * @see https://datatracker.ietf.org/doc/html/rfc6570#section-2 RFC 6570 Section 2: Syntax
 * @see https://datatracker.ietf.org/doc/html/rfc6570#section-3 RFC 6570 Section 3: Expansion
 */
final readonly class UriTemplateFormatValidator implements FormatValidatorInterface
{
    /**
     * Validate a value against the URI Template format.
     *
     * Performs validation of URI Template syntax according to RFC 6570 including:
     * - Balanced curly braces for template expressions
     * - Non-empty template expressions (rejects {})
     * - Valid variable names and operator syntax
     * - Proper character restrictions within expressions
     *
     * Note: This implements basic RFC 6570 validation. Full compliance would
     * require comprehensive operator and modifier validation.
     *
     * @param mixed $value The value to validate as a URI Template
     *
     * @return bool True if the value is a valid URI Template, false otherwise
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for balanced braces
        $openBraces = mb_substr_count($value, '{');
        $closeBraces = mb_substr_count($value, '}');

        if ($openBraces !== $closeBraces) {
            return false;
        }

        // Extract all template expressions {...}
        if (preg_match_all('/\{([^}]*)\}/', $value, $matches)) {
            foreach ($matches[1] as $expression) {
                // Expression cannot be empty
                if ($expression === '') {
                    return false;
                }

                // Basic validation of variable names
                // Per RFC 6570, variables can contain alphanumeric, _, and .
                // Operators like +, #, ?, etc. can appear at the start
                if (!preg_match('/^[+#.\/;?&=,!@|]?[a-zA-Z0-9_.,*:]+(\*)?$/', $expression)) {
                    return false;
                }
            }
        }

        // The rest should be a valid URI reference (without the template parts)
        // For now, just check for obviously invalid characters outside templates
        $withoutTemplates = preg_replace('/\{[^}]*\}/', '', $value);
        $invalidChars = ['<', '>', '"', '\\', '^', '`', ' '];

        return array_all($invalidChars, fn ($char): bool => !str_contains($withoutTemplates ?? '', (string) $char));
    }

    /**
     * Get the format name.
     *
     * @return string The format identifier 'uri-template'
     */
    public function format(): string
    {
        return 'uri-template';
    }
}
