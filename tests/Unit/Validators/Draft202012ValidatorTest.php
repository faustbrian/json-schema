<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft202012Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const INF;
use const NAN;

use function expect;
use function it;

it('returns correct supported draft', function (): void {
    $validator = new Draft202012Validator();
    expect($validator->supportedDraft())->toBe(Draft::Draft202012);
});

it('validates enum constraint with invalid enum schema', function (): void {
    $validator = new Draft202012Validator();
    // Line 93: enum must be an array
    $schema = new Schema(['enum' => 'not-an-array']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum constraint with valid values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    // Valid enum
    $result = $validator->validate('red', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid enum
    $result = $validator->validate('yellow', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum constraint with numeric values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['enum' => [1, 2, 3, 1.0]]);

    // Valid enum - integer
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid enum - float that equals integer
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid enum
    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const constraint with various types', function (): void {
    $validator = new Draft202012Validator();

    // String const
    $schema = new Schema(['const' => 'fixed-value']);
    $result = $validator->validate('fixed-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Number const
    $schema = new Schema(['const' => 42]);
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Array const
    $schema = new Schema(['const' => [1, 2, 3]]);
    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Object const
    $schema = new Schema(['const' => ['key' => 'value']]);
    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const constraint with null value', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['const' => null]);

    // Valid const - null
    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const - not null
    $result = $validator->validate('null', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const constraint with boolean values', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['const' => true]);

    // Valid const
    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const - false is different from true
    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid const - 1 is different from true
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('identifies integer floats correctly', function (): void {
    $validator = new Draft202012Validator();

    // Line 138: Tests isIntegerFloat with valid integer floats
    $schema = new Schema(['type' => 'integer']);

    // Valid integer floats (Draft 2020-12 feature)
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(42.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - float with fractional part
    $result = $validator->validate(1.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - NaN
    $result = $validator->validate(NAN, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - Infinity
    $result = $validator->validate(INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - Negative Infinity
    $result = $validator->validate(-INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates type with integer floats in Draft 2020-12', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'integer']);

    // Should accept both true integers and floats with no fractional part
    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Should reject floats with fractional parts
    $result = $validator->validate(5.1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with type coercion in Draft 2020-12', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['enum' => [1, 'one', true, null]]);

    // Each value must match exactly using JSON equality
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('one', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // These should NOT match due to type differences
    $result = $validator->validate('1', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue(); // 1.0 equals 1 in JSON equality
});

it('validates complex objects with const', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'const' => [
            'status' => 'active',
            'count' => 5,
            'metadata' => ['version' => 1],
        ],
    ]);

    // Valid - exact match
    $result = $validator->validate([
        'status' => 'active',
        'count' => 5,
        'metadata' => ['version' => 1],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - different value
    $result = $validator->validate([
        'status' => 'inactive',
        'count' => 5,
        'metadata' => ['version' => 1],
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - missing property
    $result = $validator->validate([
        'status' => 'active',
        'count' => 5,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - extra property
    $result = $validator->validate([
        'status' => 'active',
        'count' => 5,
        'metadata' => ['version' => 1],
        'extra' => 'value',
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['enum' => []]);

    // Empty enum should not match any value
    $result = $validator->validate('any', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates without enum or const keywords', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    // Should pass validation when enum/const not present
    $result = $validator->validate('any-string', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates base schema features in Draft 2020-12', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'minimum' => 0],
            'score' => ['type' => 'number', 'maximum' => 100.0],
        ],
        'required' => ['name'],
    ]);

    // Valid data
    $result = $validator->validate([
        'name' => 'John',
        'age' => 30,
        'score' => 95.5,
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - missing required
    $result = $validator->validate([
        'age' => 30,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - wrong type
    $result = $validator->validate([
        'name' => 123,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
