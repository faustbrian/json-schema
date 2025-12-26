<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Contracts\ValidatorInterface;
use Cline\JsonSchema\ValueObjects\ValidationResult;

/**
 * Lazy validator that stops on first error.
 *
 * Provides fail-fast validation that returns immediately upon encountering
 * the first validation error, rather than collecting all errors. Useful
 * for performance optimization when only validity status is needed.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LazyValidator
{
    /**
     * Validate data and stop on first error.
     *
     * Wraps any validator to provide lazy evaluation. Returns as soon as
     * the first validation error is encountered.
     *
     * @param ValidatorInterface   $validator The validator to use
     * @param mixed                $data      The data to validate
     * @param array<string, mixed> $schema    The schema to validate against
     *
     * @return ValidationResult Result with at most one error
     */
    public static function validate(
        ValidatorInterface $validator,
        mixed $data,
        array $schema,
    ): ValidationResult {
        // Use a custom error handler to stop on first error
        $result = $validator->validate($data, $schema);

        // If validation failed, return only the first error
        if ($result->isInvalid() && $result->errors !== []) {
            return ValidationResult::failure([$result->errors[0]]);
        }

        return $result;
    }

    /**
     * Check if data is valid without collecting errors.
     *
     * Most efficient validation when only boolean result is needed.
     *
     * @param ValidatorInterface   $validator The validator to use
     * @param mixed                $data      The data to validate
     * @param array<string, mixed> $schema    The schema to validate against
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(
        ValidatorInterface $validator,
        mixed $data,
        array $schema,
    ): bool {
        return self::validate($validator, $data, $schema)->isValid();
    }
}
