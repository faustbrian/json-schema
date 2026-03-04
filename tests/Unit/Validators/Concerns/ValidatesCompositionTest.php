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

// allOf Tests

it('validates allOf with all matching schemas', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'object'],
            ['minProperties' => 1],
            ['maxProperties' => 5],
        ],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails allOf when one schema does not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'object'],
            ['minProperties' => 5],
        ],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails allOf when first schema does not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'array'],
            ['minProperties' => 1],
        ],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates allOf with nested composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'object'],
            [
                'anyOf' => [
                    ['required' => ['foo']],
                    ['required' => ['bar']],
                ],
            ],
        ],
    ]);

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates allOf with empty array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when allOf is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when allOf is not an array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => 'invalid',
        'type' => 'object',
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// anyOf Tests

it('validates anyOf with one matching schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates anyOf with multiple matching schemas', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => [
            ['type' => 'object'],
            ['minProperties' => 1],
        ],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails anyOf when no schemas match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates anyOf with nested composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => [
            ['type' => 'string'],
            [
                'allOf' => [
                    ['type' => 'object'],
                    ['required' => ['foo']],
                ],
            ],
        ],
    ]);

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates anyOf with empty array fails', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => [],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when anyOf is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when anyOf is not an array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'anyOf' => 'invalid',
        'type' => 'object',
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// oneOf Tests

it('validates oneOf with exactly one matching schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails oneOf when no schemas match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails oneOf when multiple schemas match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [
            ['type' => 'object'],
            ['minProperties' => 0],
        ],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates oneOf with more than two options', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'number'],
            ['type' => 'boolean'],
            ['type' => 'null'],
        ],
    ]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates oneOf with nested composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [
            ['type' => 'string'],
            [
                'allOf' => [
                    ['type' => 'object'],
                    ['required' => ['foo']],
                ],
            ],
        ],
    ]);

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates oneOf with empty array fails', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => [],
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when oneOf is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when oneOf is not an array', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'oneOf' => 'invalid',
        'type' => 'object',
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// not Tests

it('validates not when schema does not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'not' => ['type' => 'string'],
    ]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails not when schema matches', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'not' => ['type' => 'string'],
    ]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates not with boolean schema false', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'not' => false,
    ]);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates not with boolean schema true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'not' => true,
    ]);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates not with nested composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'not' => [
            'allOf' => [
                ['type' => 'object'],
                ['required' => ['foo']],
            ],
        ],
    ]);

    $result = $validator->validate(['bar' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when not is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// Combined composition tests

it('validates combined allOf and anyOf', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'object'],
        ],
        'anyOf' => [
            ['required' => ['foo']],
            ['required' => ['bar']],
        ],
    ]);

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates combined allOf and not', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            ['type' => 'object'],
        ],
        'not' => [
            'required' => ['restricted'],
        ],
    ]);

    $result = $validator->validate(['foo' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['restricted' => 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates complex nested composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'allOf' => [
            [
                'oneOf' => [
                    ['properties' => ['type' => ['const' => 'A']]],
                    ['properties' => ['type' => ['const' => 'B']]],
                ],
            ],
            [
                'anyOf' => [
                    ['required' => ['id']],
                    ['required' => ['name']],
                ],
            ],
        ],
    ]);

    $result = $validator->validate(['type' => 'A', 'id' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});
