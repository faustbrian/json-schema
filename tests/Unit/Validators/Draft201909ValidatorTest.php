<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft201909Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const INF;
use const NAN;

use function expect;
use function it;

it('returns correct supported draft', function (): void {
    $validator = new Draft201909Validator();
    expect($validator->supportedDraft())->toBe(Draft::Draft201909);
});

it('validates enum with matching value', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('red', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates enum with non-matching value', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('yellow', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with numeric values', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => [1, 2, 3]]);

    $result = $validator->validate(2, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with mixed types', function (): void {
    $validator = new Draft201909Validator();
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

it('validates enum with objects', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => [['type' => 'admin'], ['type' => 'user']]]);

    $result = $validator->validate(['type' => 'admin'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['type' => 'guest'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with arrays', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => [[1, 2, 3], [4, 5, 6]]]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1, 2, 4], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('returns false when enum is not an array', function (): void {
    $validator = new Draft201909Validator();
    $schema = ['enum' => 'invalid'];

    $result = $validator->validate('any', $schema);
    expect($result->isValid())->toBeFalse();
});

it('passes validation when enum is not present', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const with matching string value', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => 'fixed-value']);

    $result = $validator->validate('fixed-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const with non-matching string value', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => 'fixed-value']);

    $result = $validator->validate('different-value', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with numeric value', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => 42]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(43, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with boolean value', function (): void {
    $validator = new Draft201909Validator();
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
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => null]);

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with object value', function (): void {
    $validator = new Draft201909Validator();
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
    $validator = new Draft201909Validator();
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
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for 1.0', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    // In Draft 2019-09, 1.0 is considered an integer
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for 2.0', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(2.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for negative integer floats', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(-5.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-100.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns true for zero float', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(0.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates isIntegerFloat returns false for 1.5', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(1.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for 0.1', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(0.1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for NaN', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(NAN, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for infinity', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates isIntegerFloat returns false for negative infinity', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(-INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates integer type matches actual integers', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates large integer floats', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(1_000_000.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-999_999.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const with empty string', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => '']);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(' ', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with empty array', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => []]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with empty object', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => []]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates all base validation features', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 150],
            'status' => ['const' => 'active'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30, 'status' => 'active'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['age' => -5], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with nested structures', function (): void {
    $validator = new Draft201909Validator();
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

it('validates dependentRequired keyword', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'dependentRequired' => [
            'creditCard' => ['billingAddress'],
        ],
    ]);

    // Valid - has creditCard and billingAddress
    $result = $validator->validate(['creditCard' => '1234', 'billingAddress' => '123 Main St'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid - no creditCard, no billingAddress required
    $result = $validator->validate(['email' => 'test@example.com'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - has creditCard but missing billingAddress
    $result = $validator->validate(['creditCard' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates dependentSchemas keyword', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'dependentSchemas' => [
            'creditCard' => [
                'required' => ['billingAddress'],
                'properties' => [
                    'billingAddress' => ['type' => 'string'],
                ],
            ],
        ],
    ]);

    // Valid - has creditCard and billingAddress
    $result = $validator->validate(['creditCard' => '1234', 'billingAddress' => '123 Main St'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid - no creditCard
    $result = $validator->validate(['email' => 'test@example.com'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - has creditCard but missing billingAddress
    $result = $validator->validate(['creditCard' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates if/then/else with then clause', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'if' => [
            'properties' => [
                'country' => ['const' => 'US'],
            ],
        ],
        'then' => [
            'required' => ['zipCode'],
        ],
    ]);

    // Valid - if passes, then required
    $result = $validator->validate(['country' => 'US', 'zipCode' => '12345'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - if passes, but then fails
    $result = $validator->validate(['country' => 'US'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates if/then/else with else clause', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'if' => [
            'properties' => [
                'country' => ['const' => 'US'],
            ],
        ],
        'then' => [
            'required' => ['zipCode'],
        ],
        'else' => [
            'required' => ['postalCode'],
        ],
    ]);

    // Valid - if fails, else required
    $result = $validator->validate(['country' => 'CA', 'postalCode' => 'A1A 1A1'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - if fails, but else fails
    $result = $validator->validate(['country' => 'CA'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates complex if/then/else scenario', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'memberType' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'discount' => ['type' => 'number'],
        ],
        'if' => [
            'properties' => [
                'memberType' => ['const' => 'senior'],
            ],
        ],
        'then' => [
            'properties' => [
                'age' => ['minimum' => 65],
                'discount' => ['minimum' => 0.15],
            ],
        ],
        'else' => [
            'properties' => [
                'discount' => ['maximum' => 0.1],
            ],
        ],
    ]);

    // Valid senior member
    $result = $validator->validate([
        'memberType' => 'senior',
        'age' => 70,
        'discount' => 0.2,
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid senior member - too young
    $result = $validator->validate([
        'memberType' => 'senior',
        'age' => 60,
        'discount' => 0.2,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Valid regular member
    $result = $validator->validate([
        'memberType' => 'regular',
        'age' => 30,
        'discount' => 0.05,
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid regular member - discount too high
    $result = $validator->validate([
        'memberType' => 'regular',
        'age' => 30,
        'discount' => 0.15,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with empty array rejects all values', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => []]);

    $result = $validator->validate('any', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum uses JSON equality for objects', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['enum' => [['a' => 1, 'b' => 2]]]);

    // Same structure, different order should still match
    $result = $validator->validate(['b' => 2, 'a' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates const distinguishes between 0 and false', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => 0]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const distinguishes between empty string and null', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['const' => '']);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates float with zero fractional part as integer', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(100.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-50.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates very large integer floats', function (): void {
    $validator = new Draft201909Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(9_999_999_999.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});
