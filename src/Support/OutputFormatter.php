<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Enums\OutputFormat;
use Cline\JsonSchema\ValueObjects\ValidationError;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function array_map;
use function array_values;
use function explode;
use function mb_trim;
use function sprintf;

/**
 * Formats validation results according to JSON Schema output specifications.
 *
 * Converts validation results into the four standard output formats defined
 * in JSON Schema 2020-12: Flag, Basic, Detailed, and Verbose. Each format
 * provides progressively more detail about validation failures.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/draft-bhutton-json-schema-00#section-12.4 Output Structure
 * @see https://json-schema.org/blog/posts/interpreting-output Interpreting JSON Schema Output
 */
final class OutputFormatter
{
    /**
     * Format validation result according to specified output format.
     *
     * @param ValidationResult $result The validation result to format
     * @param OutputFormat     $format The desired output format level
     *
     * @return array{valid: bool, errors?: array<array<string, mixed>>} Formatted output matching the specified format
     */
    public static function format(ValidationResult $result, OutputFormat $format): array
    {
        return match ($format) {
            OutputFormat::Flag => self::formatFlag($result),
            OutputFormat::Basic => self::formatBasic($result),
            OutputFormat::Detailed => self::formatDetailed($result),
            OutputFormat::Verbose => self::formatVerbose($result),
        };
    }

    /**
     * Format as Flag output - minimal boolean result.
     *
     * Returns only the validation status with no error details.
     * Smallest possible output format.
     *
     * @param ValidationResult $result The validation result
     *
     * @return array{valid: bool} Flag format output
     */
    private static function formatFlag(ValidationResult $result): array
    {
        return [
            'valid' => $result->valid,
        ];
    }

    /**
     * Format as Basic output - flat error list.
     *
     * Returns validation status with a flat list of all errors.
     * Each error includes path, message, and keyword.
     *
     * @param ValidationResult $result The validation result
     *
     * @return array{valid: bool, errors?: array<array{instanceLocation: string, keywordLocation: string, error: string, keyword: string}>} Basic format output
     */
    private static function formatBasic(ValidationResult $result): array
    {
        $output = [
            'valid' => $result->valid,
        ];

        if (!$result->valid) {
            $output['errors'] = array_map(
                static fn (ValidationError $error): array => [
                    'instanceLocation' => $error->path,
                    'keywordLocation' => '#'.$error->path,
                    'error' => $error->message,
                    'keyword' => $error->keyword,
                ],
                $result->errors,
            );
        }

        return $output;
    }

    /**
     * Format as Detailed output - hierarchical error structure.
     *
     * Returns validation status with nested error structures.
     * Groups related errors hierarchically by schema location.
     *
     * @param ValidationResult $result The validation result
     *
     * @return array{valid: bool, errors?: array<array<string, mixed>>} Detailed format output
     */
    private static function formatDetailed(ValidationResult $result): array
    {
        $output = [
            'valid' => $result->valid,
        ];

        if (!$result->valid) {
            $output['errors'] = self::buildHierarchy($result->errors);
        }

        return $output;
    }

    /**
     * Format as Verbose output - complete evaluation details.
     *
     * Returns the most comprehensive output including absolute schema
     * locations, complete error hierarchy, and full evaluation paths.
     *
     * @param ValidationResult $result The validation result
     *
     * @return array{valid: bool, errors?: array<array<string, mixed>>} Verbose format output
     */
    private static function formatVerbose(ValidationResult $result): array
    {
        $output = [
            'valid' => $result->valid,
        ];

        if (!$result->valid) {
            $output['errors'] = array_map(
                static fn (ValidationError $error): array => [
                    'instanceLocation' => $error->path,
                    'keywordLocation' => '#'.$error->path,
                    'absoluteKeywordLocation' => sprintf('#/%s%s', $error->keyword, $error->path),
                    'error' => $error->message,
                    'keyword' => $error->keyword,
                ],
                $result->errors,
            );
        }

        return $output;
    }

    /**
     * Build hierarchical error structure from flat error list.
     *
     * Groups errors by their path segments to create a nested
     * structure that reflects the schema hierarchy.
     *
     * @param array<ValidationError> $errors Flat list of validation errors
     *
     * @return array<array<string, mixed>> Hierarchical error structure
     */
    private static function buildHierarchy(array $errors): array
    {
        $hierarchy = [];

        foreach ($errors as $error) {
            $parts = explode('/', mb_trim($error->path, '/'));
            $current = &$hierarchy;

            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }

                /** @phpstan-ignore isset.offset */
                if (!isset($current[$part])) {
                    $current[$part] = [
                        'instanceLocation' => $error->path,
                        'keywordLocation' => '#'.$error->path,
                        'errors' => [],
                    ];
                }

                $current = &$current[$part]['errors'];
            }

            $current[] = [
                'error' => $error->message,
                'keyword' => $error->keyword,
            ];
        }

        /** @phpstan-ignore arrayValues.empty */
        return array_values($hierarchy);
    }
}
