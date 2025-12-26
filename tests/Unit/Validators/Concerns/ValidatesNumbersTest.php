<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Concerns;

use Cline\JsonSchema\Validators\Draft202012Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const PHP_FLOAT_MAX;

use function expect;
use function it;

// minimum Tests

it('validates minimum with value equal to minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minimum with value greater than minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails minimum with value less than minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate(3, $schema->toArray());
    expect($result->isValid())->toBeFalse();
    expect($result->errors())->not->toBeEmpty();
});

it('validates minimum with float values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => 5.5]);

    $result = $validator->validate(5.5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minimum with negative numbers', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => -10]);

    $result = $validator->validate(-5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-15, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when minimum is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(-1_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not numeric for minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate('not-a-number', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// maximum Tests

it('validates maximum with value equal to maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maximum with value less than maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails maximum with value greater than maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate(15, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with float values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => 10.5]);

    $result = $validator->validate(10.5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10.6, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with negative numbers', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => -5]);

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-3, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when maximum is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(1_000_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not numeric for maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate('not-a-number', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// exclusiveMinimum Tests

it('validates exclusiveMinimum with value greater than boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate(6, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails exclusiveMinimum with value equal to boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails exclusiveMinimum with value less than boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMinimum with float values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMinimum' => 5.5]);

    $result = $validator->validate(5.6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when exclusiveMinimum is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not numeric for exclusiveMinimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate('not-a-number', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// exclusiveMaximum Tests

it('validates exclusiveMaximum with value less than boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate(9, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails exclusiveMaximum with value equal to boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails exclusiveMaximum with value greater than boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMaximum with float values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMaximum' => 10.5]);

    $result = $validator->validate(10.4, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when exclusiveMaximum is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(1_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not numeric for exclusiveMaximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate('not-a-number', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// multipleOf Tests

it('validates multipleOf with exact multiple', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 5]);

    $result = $validator->validate(15, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails multipleOf with non-multiple', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 5]);

    $result = $validator->validate(16, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates multipleOf with float divisor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 0.5]);

    $result = $validator->validate(2.5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(2.6, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates multipleOf with float values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 0.01]);

    $result = $validator->validate(1.23, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates multipleOf with zero value', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 5]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates multipleOf with negative numbers', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 3]);

    $result = $validator->validate(-9, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates multipleOf with power of two divisor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 2]);

    $result = $validator->validate(8, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates multipleOf with large numbers', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 1_000]);

    $result = $validator->validate(5_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5_001, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates multipleOf with very small divisor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 0.001]);

    $result = $validator->validate(0.003, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates multipleOf with extremely large number causing overflow', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 2]); // Power of 2

    // Extremely large number that would cause division overflow
    $result = $validator->validate(PHP_FLOAT_MAX, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates multipleOf with power of two and large number', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 4]); // Power of 2

    $result = $validator->validate(1e308, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails multipleOf with non-power-of-two and overflow', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 0.3]); // NOT a power of 2

    // Very large number that would cause division overflow (result is INF)
    $result = $validator->validate(PHP_FLOAT_MAX, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when multipleOf is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(7, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not numeric for multipleOf', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['multipleOf' => 5]);

    $result = $validator->validate('not-a-number', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// Combined numeric validations

it('validates combined minimum and maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'minimum' => 5,
        'maximum' => 10,
    ]);

    $result = $validator->validate(7, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(3, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(12, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined exclusive boundaries', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'exclusiveMinimum' => 0,
        'exclusiveMaximum' => 100,
    ]);

    $result = $validator->validate(50, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(100, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined multipleOf and range', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'multipleOf' => 3,
        'minimum' => 10,
        'maximum' => 30,
    ]);

    $result = $validator->validate(12, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(13, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
