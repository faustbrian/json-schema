<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\CannotResolveReferenceException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\UnresolvedReferenceException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with factory method', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/User');

    expect($exception)->toBeInstanceOf(CannotResolveReferenceException::class)
        ->and($exception->getMessage())->toBe('Unable to resolve reference: #/definitions/User');
});

it('creates exception with different reference formats', function (): void {
    // JSON Pointer reference
    $exception = CannotResolveReferenceException::forReference('#/properties/name');
    expect($exception->getMessage())->toBe('Unable to resolve reference: #/properties/name');

    // URI reference
    $exception = CannotResolveReferenceException::forReference('https://example.com/schema.json');
    expect($exception->getMessage())->toBe('Unable to resolve reference: https://example.com/schema.json');

    // Relative reference
    $exception = CannotResolveReferenceException::forReference('../schemas/common.json#/definitions/Address');
    expect($exception->getMessage())->toBe('Unable to resolve reference: ../schemas/common.json#/definitions/Address');
});

it('creates exception with empty reference', function (): void {
    $exception = CannotResolveReferenceException::forReference('');

    expect($exception->getMessage())->toBe('Unable to resolve reference: ');
});

it('creates exception with special characters in reference', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/User%20Profile');

    expect($exception->getMessage())->toBe('Unable to resolve reference: #/definitions/User%20Profile');
});

it('extends UnresolvedReferenceException', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/Missing');

    expect($exception)->toBeInstanceOf(UnresolvedReferenceException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/Missing');

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/Missing');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw CannotResolveReferenceException::forReference('#/missing'))
        ->toThrow(CannotResolveReferenceException::class);
});

it('can be caught as UnresolvedReferenceException', function (): void {
    expect(fn (): mixed => throw CannotResolveReferenceException::forReference('#/missing'))
        ->toThrow(UnresolvedReferenceException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw CannotResolveReferenceException::forReference('#/missing'))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw CannotResolveReferenceException::forReference('#/missing'))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(CannotResolveReferenceException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = CannotResolveReferenceException::forReference('#/definitions/User');

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates multiple distinct exceptions', function (): void {
    $exception1 = CannotResolveReferenceException::forReference('#/definitions/User');
    $exception2 = CannotResolveReferenceException::forReference('#/definitions/Post');

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->not->toBe($exception2->getMessage());
});
