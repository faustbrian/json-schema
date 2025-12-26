<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\InvalidSchemaException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\MissingSchemaKeywordException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with factory method', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('type');

    expect($exception)->toBeInstanceOf(MissingSchemaKeywordException::class)
        ->and($exception->getMessage())->toBe('Schema is missing required keyword: type');
});

it('creates exception with different keywords', function (): void {
    // type keyword
    $exception = MissingSchemaKeywordException::forKeyword('type');
    expect($exception->getMessage())->toBe('Schema is missing required keyword: type');

    // properties keyword
    $exception = MissingSchemaKeywordException::forKeyword('properties');
    expect($exception->getMessage())->toBe('Schema is missing required keyword: properties');

    // $schema keyword
    $exception = MissingSchemaKeywordException::forKeyword('$schema');
    expect($exception->getMessage())->toBe('Schema is missing required keyword: $schema');

    // required keyword
    $exception = MissingSchemaKeywordException::forKeyword('required');
    expect($exception->getMessage())->toBe('Schema is missing required keyword: required');
});

it('creates exception with empty keyword', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('');

    expect($exception->getMessage())->toBe('Schema is missing required keyword: ');
});

it('creates exception with custom keyword', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('customValidator');

    expect($exception->getMessage())->toBe('Schema is missing required keyword: customValidator');
});

it('creates exception with special characters in keyword', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('x-custom-field');

    expect($exception->getMessage())->toBe('Schema is missing required keyword: x-custom-field');
});

it('extends InvalidSchemaException', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('type');

    expect($exception)->toBeInstanceOf(InvalidSchemaException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('type');

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('type');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw MissingSchemaKeywordException::forKeyword('type'))
        ->toThrow(MissingSchemaKeywordException::class);
});

it('can be caught as InvalidSchemaException', function (): void {
    expect(fn (): mixed => throw MissingSchemaKeywordException::forKeyword('type'))
        ->toThrow(InvalidSchemaException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw MissingSchemaKeywordException::forKeyword('type'))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw MissingSchemaKeywordException::forKeyword('type'))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(MissingSchemaKeywordException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = MissingSchemaKeywordException::forKeyword('type');

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates multiple distinct exceptions', function (): void {
    $exception1 = MissingSchemaKeywordException::forKeyword('type');
    $exception2 = MissingSchemaKeywordException::forKeyword('properties');

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->not->toBe($exception2->getMessage());
});

it('creates same message for same keyword', function (): void {
    $exception1 = MissingSchemaKeywordException::forKeyword('type');
    $exception2 = MissingSchemaKeywordException::forKeyword('type');

    expect($exception1->getMessage())->toBe($exception2->getMessage());
});
