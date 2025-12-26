<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\InvalidJsonPointerException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\UnresolvedReferenceException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with factory method', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/invalid/pointer');

    expect($exception)->toBeInstanceOf(InvalidJsonPointerException::class)
        ->and($exception->getMessage())->toBe('Invalid JSON pointer: /invalid/pointer');
});

it('creates exception with different pointer formats', function (): void {
    // Standard JSON Pointer
    $exception = InvalidJsonPointerException::forPointer('/properties/name');
    expect($exception->getMessage())->toBe('Invalid JSON pointer: /properties/name');

    // Pointer with escaped characters
    $exception = InvalidJsonPointerException::forPointer('/properties/user~0name');
    expect($exception->getMessage())->toBe('Invalid JSON pointer: /properties/user~0name');

    // Pointer with array index
    $exception = InvalidJsonPointerException::forPointer('/items/0');
    expect($exception->getMessage())->toBe('Invalid JSON pointer: /items/0');
});

it('creates exception with empty pointer', function (): void {
    $exception = InvalidJsonPointerException::forPointer('');

    expect($exception->getMessage())->toBe('Invalid JSON pointer: ');
});

it('creates exception with malformed pointer', function (): void {
    // Missing leading slash
    $exception = InvalidJsonPointerException::forPointer('properties/name');
    expect($exception->getMessage())->toBe('Invalid JSON pointer: properties/name');

    // Invalid escape sequence
    $exception = InvalidJsonPointerException::forPointer('/properties/~2invalid');
    expect($exception->getMessage())->toBe('Invalid JSON pointer: /properties/~2invalid');
});

it('creates exception with special characters', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/properties/user name');

    expect($exception->getMessage())->toBe('Invalid JSON pointer: /properties/user name');
});

it('extends UnresolvedReferenceException', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/invalid');

    expect($exception)->toBeInstanceOf(UnresolvedReferenceException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/invalid');

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/invalid');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw InvalidJsonPointerException::forPointer('/invalid'))
        ->toThrow(InvalidJsonPointerException::class);
});

it('can be caught as UnresolvedReferenceException', function (): void {
    expect(fn (): mixed => throw InvalidJsonPointerException::forPointer('/invalid'))
        ->toThrow(UnresolvedReferenceException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw InvalidJsonPointerException::forPointer('/invalid'))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw InvalidJsonPointerException::forPointer('/invalid'))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(InvalidJsonPointerException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = InvalidJsonPointerException::forPointer('/invalid');

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates multiple distinct exceptions', function (): void {
    $exception1 = InvalidJsonPointerException::forPointer('/pointer1');
    $exception2 = InvalidJsonPointerException::forPointer('/pointer2');

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->not->toBe($exception2->getMessage());
});
