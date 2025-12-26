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

use function expect;
use function it;

// items Tests

it('validates items with single schema for all items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => ['type' => 'string'],
    ]);

    $result = $validator->validate(['a', 'b', 'c'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails items when one item does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => ['type' => 'string'],
    ]);

    $result = $validator->validate(['a', 'b', 123], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates items with tuple schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
        ],
    ]);

    $result = $validator->validate(['hello', 42, true], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates items with tuple schema and fewer items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
        ],
    ]);

    $result = $validator->validate(['hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates items with prefixItems and items for additional items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
        'items' => ['type' => 'boolean'],
    ]);

    $result = $validator->validate(['hello', 42, true, false], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails items with prefixItems when additional items do not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'items' => ['type' => 'boolean'],
    ]);

    $result = $validator->validate(['hello', 'invalid'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates items with false to disallow items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'items' => false,
    ]);

    $result = $validator->validate(['hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['hello', 'extra'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when items is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not an array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['items' => ['type' => 'string']]);

    $result = $validator->validate('not-an-array', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates items with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => ['type' => 'string'],
    ]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// minItems Tests

it('validates minItems with sufficient items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minItems' => 2]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minItems with exact count', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minItems' => 3]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails minItems with insufficient items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minItems' => 5]);

    $result = $validator->validate([1, 2], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minItems with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minItems' => 0]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when minItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// maxItems Tests

it('validates maxItems with fewer items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxItems' => 3]);

    $result = $validator->validate([1, 2], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maxItems with exact count', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxItems' => 3]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails maxItems with too many items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxItems' => 2]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maxItems with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxItems' => 0]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when maxItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([1, 2, 3, 4, 5], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// uniqueItems Tests

it('validates uniqueItems with all unique items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails uniqueItems with duplicate primitives', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([1, 2, 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails uniqueItems with duplicate objects', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([['a' => 1], ['a' => 1]], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates uniqueItems with objects in different order', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1]], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates uniqueItems with different objects', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([['a' => 1], ['a' => 2]], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates uniqueItems with nested arrays', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([[1, 2], [1, 2]], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates uniqueItems when false', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => false]);

    $result = $validator->validate([1, 2, 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates uniqueItems with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['uniqueItems' => true]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when uniqueItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([1, 1, 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// contains Tests

it('validates contains with matching item', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
    ]);

    $result = $validator->validate([1, 2, 'hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contains with multiple matching items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
    ]);

    $result = $validator->validate(['a', 'b', 'c'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contains when no items match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
    ]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contains with minContains', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
        'minContains' => 2,
    ]);

    $result = $validator->validate(['a', 'b', 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contains with minContains when not enough matches', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
        'minContains' => 3,
    ]);

    $result = $validator->validate(['a', 'b', 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contains with maxContains', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
        'maxContains' => 2,
    ]);

    $result = $validator->validate(['a', 'b', 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contains with maxContains when too many matches', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
        'maxContains' => 1,
    ]);

    $result = $validator->validate(['a', 'b', 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contains with minContains zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'contains' => ['type' => 'string'],
        'minContains' => 0,
    ]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when contains is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// prefixItems Tests

it('validates prefixItems with matching tuple', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
        ],
    ]);

    $result = $validator->validate(['hello', 42, true], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails prefixItems when item does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['hello', 'wrong'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates prefixItems with fewer items than schemas', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
        ],
    ]);

    $result = $validator->validate(['hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates prefixItems with more items than schemas', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['hello', 42, true], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates prefixItems with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when prefixItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'array']);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// additionalItems Tests (legacy)

it('validates additionalItems with schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
        'additionalItems' => ['type' => 'boolean'],
    ]);

    $result = $validator->validate(['hello', 42, true, false], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails additionalItems when false and items beyond tuple', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
        ],
        'additionalItems' => false,
    ]);

    $result = $validator->validate(['hello', 42], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates additionalItems when false and no extra items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
        ],
        'additionalItems' => false,
    ]);

    $result = $validator->validate(['hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates additionalItems when true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
        ],
        'additionalItems' => true,
    ]);

    $result = $validator->validate(['hello', 42, true], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when additionalItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'items' => [
            ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['hello', 42], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// unevaluatedItems Tests

it('validates unevaluatedItems with all items evaluated', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'unevaluatedItems' => false,
    ]);

    $result = $validator->validate(['hello'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails unevaluatedItems when false and unevaluated items exist', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'unevaluatedItems' => false,
    ]);

    $result = $validator->validate(['hello', 42], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates unevaluatedItems with schema for unevaluated items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'unevaluatedItems' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['hello', 42, 100], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails unevaluatedItems when unevaluated item does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'unevaluatedItems' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['hello', 'invalid'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates unevaluatedItems when true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
        'unevaluatedItems' => true,
    ]);

    $result = $validator->validate(['hello', 'anything', 42], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates unevaluatedItems with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'unevaluatedItems' => false,
    ]);

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when unevaluatedItems is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'prefixItems' => [
            ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['hello', 42], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});
