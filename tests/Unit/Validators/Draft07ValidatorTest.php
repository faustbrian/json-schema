<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const INF;
use const NAN;

use function expect;
use function it;

it('returns correct supported draft', function (): void {
    $validator = new Draft07Validator();
    expect($validator->supportedDraft())->toBe(Draft::Draft07);
});

it('validates const constraint', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['const' => 'fixed-value']);

    // Valid const
    $result = $validator->validate('fixed-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const
    $result = $validator->validate('different-value', $schema->toArray());
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(1)
        ->and($result->errors()[0]->keyword())->toBe('const');
});

it('validates const constraint with numeric values', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['const' => 42]);

    // Valid const
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const
    $result = $validator->validate(43, $schema->toArray());
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(1)
        ->and($result->errors()[0]->keyword())->toBe('const');
});

it('validates contains constraint', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'type' => 'array',
        'contains' => ['type' => 'integer'],
    ]);

    // Valid contains
    $result = $validator->validate([1, 'two', 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid contains - no matching items
    $result = $validator->validate(['one', 'two', 'three'], $schema->toArray());
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(1)
        ->and($result->errors()[0]->keyword())->toBe('contains');
});

it('validates if/then/else with then clause', function (): void {
    $validator = new Draft07Validator();
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
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(1)
        ->and($result->errors()[0]->keyword())->toBe('required');
});

it('validates if/then/else with else clause', function (): void {
    $validator = new Draft07Validator();
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
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(1)
        ->and($result->errors()[0]->keyword())->toBe('required');
});

it('validates if/then without else', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'type' => 'object',
        'if' => [
            'properties' => [
                'premium' => ['const' => true],
            ],
        ],
        'then' => [
            'required' => ['subscriptionId'],
        ],
    ]);

    // Valid - if passes, then satisfied
    $result = $validator->validate(['premium' => true, 'subscriptionId' => '123'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid - if fails, no else to satisfy
    $result = $validator->validate(['premium' => false], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - if passes, then fails
    $result = $validator->validate(['premium' => true], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates complex if/then/else scenario', function (): void {
    $validator = new Draft07Validator();
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

it('validates nested if/then/else', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'type' => 'object',
        'if' => [
            'properties' => [
                'type' => ['const' => 'person'],
            ],
        ],
        'then' => [
            'required' => ['name'],
            'if' => [
                'properties' => [
                    'employed' => ['const' => true],
                ],
            ],
            'then' => [
                'required' => ['company'],
            ],
        ],
    ]);

    // Valid employed person
    $result = $validator->validate([
        'type' => 'person',
        'name' => 'John',
        'employed' => true,
        'company' => 'ACME',
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid employed person - missing company
    $result = $validator->validate([
        'type' => 'person',
        'name' => 'John',
        'employed' => true,
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates all base Draft05 features', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'minimum' => 0],
        ],
    ]);

    // Valid data
    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid data
    $result = $validator->validate(['age' => -5], $schema->toArray());
    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(2); // Missing required + invalid minimum
});

it('validates const with objects', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'const' => ['status' => 'active', 'type' => 'user'],
    ]);

    // Valid const object
    $result = $validator->validate(['status' => 'active', 'type' => 'user'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const object - different values
    $result = $validator->validate(['status' => 'inactive', 'type' => 'user'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates const with arrays', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['const' => [1, 2, 3]]);

    // Valid const array
    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid const array - different values
    $result = $validator->validate([1, 2, 4], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contains with complex item schemas', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'type' => 'array',
        'contains' => [
            'type' => 'object',
            'required' => ['id', 'active'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'active' => ['const' => true],
            ],
        ],
    ]);

    // Valid - contains at least one matching item
    $result = $validator->validate([
        ['id' => 1, 'active' => false],
        ['id' => 2, 'active' => true],
        ['id' => 3, 'active' => false],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - no matching items
    $result = $validator->validate([
        ['id' => 1, 'active' => false],
        ['id' => 2, 'active' => false],
    ], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum constraint with invalid enum schema', function (): void {
    $validator = new Draft07Validator();
    // Line 75: enum must be an array
    $schema = new Schema(['enum' => 'not-an-array']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with various data types', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['enum' => [1, 'one', true, null, [1, 2], ['key' => 'value']]]);

    // Valid enum values
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('one', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1, 2], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid enum value
    $result = $validator->validate('two', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('identifies integer floats correctly in Draft 07', function (): void {
    $validator = new Draft07Validator();

    // Line 125: Tests isIntegerFloat with edge cases
    $schema = new Schema(['type' => 'integer']);

    // Valid integer floats (Draft 07+ feature)
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(42.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - float with fractional part
    $result = $validator->validate(1.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - NaN (line 125)
    $result = $validator->validate(NAN, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - Infinity (line 125)
    $result = $validator->validate(INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - Negative Infinity (line 125)
    $result = $validator->validate(-INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $ref overrides siblings in Draft 07', function (): void {
    $validator = new Draft07Validator();
    // In Draft 07, $ref should override sibling keywords
    // This tests the refOverridesSiblings method
    $schema = new Schema([
        '$ref' => '#/definitions/number',
        'type' => 'string', // This should be ignored
        'definitions' => [
            'number' => ['type' => 'number'],
        ],
    ]);

    // Should validate as number (from $ref), not string
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // String should fail because $ref to number is used
    $result = $validator->validate('text', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
