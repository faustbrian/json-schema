<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Exceptions\DraftNotSupportedException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\UnsupportedDraftException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with Draft04', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft04);

    expect($exception)->toBeInstanceOf(DraftNotSupportedException::class)
        ->and($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 04');
});

it('creates exception with Draft06', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft06);

    expect($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 06');
});

it('creates exception with Draft07', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 07');
});

it('creates exception with Draft201909', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft201909);

    expect($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 2019-09');
});

it('creates exception with Draft202012', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft202012);

    expect($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 2020-12');
});

it('creates different messages for different drafts', function (): void {
    $exception1 = DraftNotSupportedException::forDraft(Draft::Draft04);
    $exception2 = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception1->getMessage())->not->toBe($exception2->getMessage());
});

it('extends UnsupportedDraftException', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception)->toBeInstanceOf(UnsupportedDraftException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw DraftNotSupportedException::forDraft(Draft::Draft04))
        ->toThrow(DraftNotSupportedException::class);
});

it('can be caught as UnsupportedDraftException', function (): void {
    expect(fn (): mixed => throw DraftNotSupportedException::forDraft(Draft::Draft04))
        ->toThrow(UnsupportedDraftException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw DraftNotSupportedException::forDraft(Draft::Draft04))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw DraftNotSupportedException::forDraft(Draft::Draft04))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(DraftNotSupportedException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft07);

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates multiple distinct exceptions', function (): void {
    $exception1 = DraftNotSupportedException::forDraft(Draft::Draft04);
    $exception2 = DraftNotSupportedException::forDraft(Draft::Draft04);

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->toBe($exception2->getMessage());
});

it('uses draft label method correctly', function (): void {
    $exception = DraftNotSupportedException::forDraft(Draft::Draft202012);

    expect($exception->getMessage())->toContain('Draft 2020-12')
        ->and($exception->getMessage())->toBe('Unsupported JSON Schema draft: Draft 2020-12');
});
