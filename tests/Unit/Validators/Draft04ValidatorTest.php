<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use function expect;
use function it;

it('returns correct supported draft', function (): void {
    $validator = new Draft04Validator();
    expect($validator->supportedDraft())->toBe(Draft::Draft04);
});

it('validates enum with matching value', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('red', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates enum with non-matching value', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);

    $result = $validator->validate('yellow', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with numeric values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => [1, 2, 3]]);

    $result = $validator->validate(2, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with mixed types', function (): void {
    $validator = new Draft04Validator();
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
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => [['type' => 'admin'], ['type' => 'user']]]);

    $result = $validator->validate(['type' => 'admin'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['type' => 'guest'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum with arrays', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => [[1, 2, 3], [4, 5, 6]]]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1, 2, 4], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('returns false when enum is not an array', function (): void {
    $validator = new Draft04Validator();
    $schema = ['enum' => 'invalid'];

    $result = $validator->validate('any', $schema);
    expect($result->isValid())->toBeFalse();
});

it('passes validation when enum is not present', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maximum with inclusive comparison', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(9, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with exclusiveMaximum as boolean modifier', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10, 'exclusiveMaximum' => true]);

    $result = $validator->validate(9, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with exclusiveMaximum false', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10, 'exclusiveMaximum' => false]);

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with float values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10.5]);

    $result = $validator->validate(10.5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10.6, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maximum with exclusive float values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10.5, 'exclusiveMaximum' => true]);

    $result = $validator->validate(10.4, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('ignores maximum when data is not numeric', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 10]);

    $result = $validator->validate('string', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when maximum is not present', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(999, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minimum with inclusive comparison', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minimum with exclusiveMinimum as boolean modifier', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5, 'exclusiveMinimum' => true]);

    $result = $validator->validate(6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minimum with exclusiveMinimum false', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5, 'exclusiveMinimum' => false]);

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minimum with float values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5.5]);

    $result = $validator->validate(5.5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minimum with exclusive float values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5.5, 'exclusiveMinimum' => true]);

    $result = $validator->validate(5.6, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('ignores minimum when data is not numeric', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5]);

    $result = $validator->validate('string', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when minimum is not present', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['type' => 'number']);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates exclusiveMaximum returns true when not used with maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['exclusiveMaximum' => true]);

    $result = $validator->validate(999, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates exclusiveMinimum returns true when not used with minimum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['exclusiveMinimum' => true]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates ref overrides siblings in Draft 04', function (): void {
    $validator = new Draft04Validator();
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
                'maximum' => 100, // This should be ignored in Draft 04
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

it('validates combined minimum and maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 5, 'maximum' => 10]);

    $result = $validator->validate(7, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(4, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined exclusive minimum and maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema([
        'minimum' => 5,
        'maximum' => 10,
        'exclusiveMinimum' => true,
        'exclusiveMaximum' => true,
    ]);

    $result = $validator->validate(7, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(10, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates all base validation features', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema([
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 150],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['age' => -5], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates negative numbers with minimum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => -10]);

    $result = $validator->validate(-5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-11, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates negative numbers with maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => -5]);

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-5, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-4, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates zero with minimum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 0]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates zero with maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['maximum' => 0]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates edge case with same minimum and maximum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 42, 'maximum' => 42]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(41, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(43, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates large numbers', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 1_000_000, 'maximum' => 9_999_999]);

    $result = $validator->validate(5_000_000, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(999_999, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(10_000_000, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates very small float increments', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['minimum' => 0.000_1, 'maximum' => 0.000_2]);

    $result = $validator->validate(0.000_15, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0.000_09, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(0.000_21, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates integer type does not match 1.0 in Draft 04', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['type' => 'integer']);

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // In Draft 04, 1.0 is NOT considered an integer
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates empty enum array rejects all values', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => []]);

    $result = $validator->validate('any', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates enum uses JSON equality for objects', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => [['a' => 1, 'b' => 2]]]);

    // Same structure, different order should still match
    $result = $validator->validate(['b' => 2, 'a' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates boolean schemas with enum', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['enum' => [true, false]]);

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
