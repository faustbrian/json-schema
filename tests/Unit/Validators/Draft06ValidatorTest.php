<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft06Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const INF;
use const NAN;

use function expect;
use function it;

it('returns correct supported draft', function (): void {
    $validator = new Draft06Validator();
    expect($validator->supportedDraft())->toBe(Draft::Draft06);
});

it('validates enum with matching value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('red', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates enum with non-matching value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('yellow', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with numeric values', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['enum' => [1, 2, 3]]);

    $result = $validator->validate(2, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with mixed types', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['enum' => [1, 'two', true, null]]);

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('two', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(2, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('returns false when enum is not an array', function (): void {
    $validator = new Draft06Validator();
    $schema = ['enum' => 'invalid'];

    $result = $validator->validate('any', $schema);
    expect($result->isValid())->toBeFalse();
});

it('passes validation when enum is not present', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const with matching string value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => 'fixed-value']);

    $result = $validator->validate('fixed-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const with non-matching string value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => 'fixed-value']);

    $result = $validator->validate('different-value', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with numeric value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => 42]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(43, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with boolean value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => true]);

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Truthy value should not match
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with null value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => null]);

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with object value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => ['type' => 'admin', 'level' => 5]]);

    $result = $validator->validate(['type' => 'admin', 'level' => 5], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Different property order but same structure should match
    $result = $validator->validate(['level' => 5, 'type' => 'admin'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['type' => 'admin', 'level' => 4], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with array value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => [1, 2, 3]]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1, 2, 4], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Different order should not match for arrays
    $result = $validator->validate([3, 2, 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when const is not present', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates exclusiveMaximum as numeric value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate(9, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMaximum with float values', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => 10.5]);

    $result = $validator->validate(10.4, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(10.6, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMaximum with integer data', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => 100]);

    $result = $validator->validate(99, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(100, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('ignores exclusiveMaximum when data is not numeric', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => 10]);

    $result = $validator->validate('string', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when exclusiveMaximum is not present', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(999, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates exclusiveMinimum as numeric value', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate(6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMinimum with float values', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 5.5]);

    $result = $validator->validate(5.6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(5.4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates exclusiveMinimum with integer data', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 0]);

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('ignores exclusiveMinimum when data is not numeric', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 5]);

    $result = $validator->validate('string', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when exclusiveMinimum is not present', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for 1.0', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    // In Draft 06+, 1.0 is considered an integer
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for 2.0', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(2.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns false for 1.5', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(1.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for NaN', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(NAN, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for infinity', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for negative infinity', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(-INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates integer type matches actual integers', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates ref overrides siblings in Draft 06', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema([
        'definitions' => [
            'positive' => [
                'type' => 'number',
                'minimum' => 0,
            ],
        ],
        'type' => 'object',
        'properties' => [
            'value' => [
                '$ref' => '#/definitions/positive',
                'maximum' => 100, // This should be ignored in Draft 06
            ],
        ],
    ]);

    // Value of 150 would fail if maximum was respected, but $ref overrides it
    $result = $validator->validate(['value' => 150], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // But it still respects the referenced schema's minimum
    $result = $validator->validate(['value' => -5], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined exclusiveMinimum and exclusiveMaximum', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 5, 'exclusiveMaximum' => 10]);

    $result = $validator->validate(7, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates negative numbers with exclusiveMinimum', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => -10]);

    $result = $validator->validate(-9, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(-11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates negative numbers with exclusiveMaximum', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => -5]);

    $result = $validator->validate(-6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(-4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates zero with exclusiveMinimum', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 0]);

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(-1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates zero with exclusiveMaximum', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMaximum' => 0]);

    $result = $validator->validate(-1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates large numbers with exclusive bounds', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 1_000_000, 'exclusiveMaximum' => 9_999_999]);

    $result = $validator->validate(5_000_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(1_000_000, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(9_999_999, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates very small float increments with exclusive bounds', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['exclusiveMinimum' => 0.000_1, 'exclusiveMaximum' => 0.000_2]);

    $result = $validator->validate(0.000_15, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0.000_1, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(0.000_2, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with empty string', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => '']);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(' ', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with empty array', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => []]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with empty object', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['const' => []]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates all base validation features', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema([
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 150],
            'status' => ['const' => 'active'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30, 'status' => 'active'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['age' => 0], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with nested structures', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema([
        'const' => [
            'user' => [
                'role' => 'admin',
                'permissions' => ['read', 'write'],
            ],
        ],
    ]);

    $result = $validator->validate([
        'user' => [
            'role' => 'admin',
            'permissions' => ['read', 'write'],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([
        'user' => [
            'role' => 'admin',
            'permissions' => ['read'],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates float with zero fractional part as integer', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(100.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-50.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates enum with empty array rejects all values', function (): void {
    $validator = new Draft06Validator();
    $schema = new Schema(['enum' => []]);

    $result = $validator->validate('any', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
