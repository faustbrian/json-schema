<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;

use function array_key_exists;
use function array_keys;

/**
 * Registry for custom format validators.
 *
 * Manages registration and retrieval of format validators, allowing users
 * to extend the library with custom string format validation logic beyond
 * the built-in formats defined in the JSON Schema specification.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format Format Validation
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-7 Format Annotation
 */
final class FormatRegistry
{
    /**
     * Registered custom format validators.
     *
     * @var array<string, FormatValidatorInterface>
     */
    private static array $customValidators = [];

    /**
     * Register a custom format validator.
     *
     * Allows registration of custom format validation logic for use in schema validation.
     * The format name can be any string and will be matched against the "format" keyword
     * in schemas.
     *
     * @param string                   $formatName The name of the format (e.g., 'credit-card', 'phone')
     * @param FormatValidatorInterface $validator  The validator implementation
     */
    public static function register(string $formatName, FormatValidatorInterface $validator): void
    {
        self::$customValidators[$formatName] = $validator;
    }

    /**
     * Check if a custom format validator is registered.
     *
     * @param string $formatName The format name to check
     *
     * @return bool True if a custom validator exists for this format
     */
    public static function has(string $formatName): bool
    {
        return array_key_exists($formatName, self::$customValidators);
    }

    /**
     * Get a custom format validator.
     *
     * @param string $formatName The format name to retrieve
     *
     * @return null|FormatValidatorInterface The validator if registered, null otherwise
     */
    public static function get(string $formatName): ?FormatValidatorInterface
    {
        return self::$customValidators[$formatName] ?? null;
    }

    /**
     * Unregister a custom format validator.
     *
     * @param string $formatName The format name to remove
     */
    public static function unregister(string $formatName): void
    {
        unset(self::$customValidators[$formatName]);
    }

    /**
     * Clear all custom format validators.
     *
     * Useful for testing or resetting the registry state.
     */
    public static function clear(): void
    {
        self::$customValidators = [];
    }

    /**
     * Get all registered custom format names.
     *
     * @return array<string> List of registered format names
     */
    public static function getRegisteredFormats(): array
    {
        return array_keys(self::$customValidators);
    }
}
