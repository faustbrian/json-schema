<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\DraftCannotBeDetectedException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\UnsupportedDraftException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with factory method', function (): void {
    $exception = DraftCannotBeDetectedException::fromSchema();

    expect($exception)->toBeInstanceOf(DraftCannotBeDetectedException::class)
        ->and($exception->getMessage())->toBe('Unable to detect JSON Schema draft version from schema');
});

it('has consistent message across multiple instances', function (): void {
    $exception1 = DraftCannotBeDetectedException::fromSchema();
    $exception2 = DraftCannotBeDetectedException::fromSchema();

    expect($exception1->getMessage())->toBe($exception2->getMessage())
        ->and($exception1->getMessage())->toBe('Unable to detect JSON Schema draft version from schema');
});

it('extends UnsupportedDraftException', function (): void {
    $exception = DraftCannotBeDetectedException::fromSchema();

    expect($exception)->toBeInstanceOf(UnsupportedDraftException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = DraftCannotBeDetectedException::fromSchema();

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = DraftCannotBeDetectedException::fromSchema();

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw DraftCannotBeDetectedException::fromSchema())
        ->toThrow(DraftCannotBeDetectedException::class);
});

it('can be caught as UnsupportedDraftException', function (): void {
    expect(fn (): mixed => throw DraftCannotBeDetectedException::fromSchema())
        ->toThrow(UnsupportedDraftException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw DraftCannotBeDetectedException::fromSchema())
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw DraftCannotBeDetectedException::fromSchema())
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(DraftCannotBeDetectedException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = DraftCannotBeDetectedException::fromSchema();

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates distinct exception instances', function (): void {
    $exception1 = DraftCannotBeDetectedException::fromSchema();
    $exception2 = DraftCannotBeDetectedException::fromSchema();

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->toBe($exception2->getMessage());
});
