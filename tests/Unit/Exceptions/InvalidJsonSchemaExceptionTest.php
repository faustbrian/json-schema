<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\InvalidJsonSchemaException;
use Cline\JsonSchema\Exceptions\InvalidSchemaException;
use Cline\JsonSchema\Exceptions\JsonSchemaException;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with factory method', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('malformed JSON syntax');

    expect($exception)->toBeInstanceOf(InvalidJsonSchemaException::class)
        ->and($exception->getMessage())->toBe('Invalid JSON schema: malformed JSON syntax');
});

it('creates exception with different reasons', function (): void {
    // Malformed JSON
    $exception = InvalidJsonSchemaException::fromReason('malformed JSON syntax');
    expect($exception->getMessage())->toBe('Invalid JSON schema: malformed JSON syntax');

    // Invalid $schema value
    $exception = InvalidJsonSchemaException::fromReason('invalid $schema value');
    expect($exception->getMessage())->toBe('Invalid JSON schema: invalid $schema value');

    // Missing required structure
    $exception = InvalidJsonSchemaException::fromReason('missing required structure');
    expect($exception->getMessage())->toBe('Invalid JSON schema: missing required structure');
});

it('creates exception with empty reason', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('');

    expect($exception->getMessage())->toBe('Invalid JSON schema: ');
});

it('creates exception with detailed reason', function (): void {
    $reason = 'Unexpected token } in JSON at position 42';
    $exception = InvalidJsonSchemaException::fromReason($reason);

    expect($exception->getMessage())->toBe('Invalid JSON schema: '.$reason);
});

it('creates exception with multiline reason', function (): void {
    $reason = "Multiple errors:\n- Missing opening brace\n- Invalid property name";
    $exception = InvalidJsonSchemaException::fromReason($reason);

    expect($exception->getMessage())->toContain('Invalid JSON schema:')
        ->and($exception->getMessage())->toContain('Multiple errors:');
});

it('extends InvalidSchemaException', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('test reason');

    expect($exception)->toBeInstanceOf(InvalidSchemaException::class);
});

it('extends JsonSchemaException', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('test reason');

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('test reason');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    expect(fn (): mixed => throw InvalidJsonSchemaException::fromReason('invalid'))
        ->toThrow(InvalidJsonSchemaException::class);
});

it('can be caught as InvalidSchemaException', function (): void {
    expect(fn (): mixed => throw InvalidJsonSchemaException::fromReason('invalid'))
        ->toThrow(InvalidSchemaException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    expect(fn (): mixed => throw InvalidJsonSchemaException::fromReason('invalid'))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    expect(fn (): mixed => throw InvalidJsonSchemaException::fromReason('invalid'))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(InvalidJsonSchemaException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $exception = InvalidJsonSchemaException::fromReason('test reason');

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates multiple distinct exceptions', function (): void {
    $exception1 = InvalidJsonSchemaException::fromReason('reason one');
    $exception2 = InvalidJsonSchemaException::fromReason('reason two');

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->not->toBe($exception2->getMessage());
});

it('creates same message for same reason', function (): void {
    $exception1 = InvalidJsonSchemaException::fromReason('malformed JSON');
    $exception2 = InvalidJsonSchemaException::fromReason('malformed JSON');

    expect($exception1->getMessage())->toBe($exception2->getMessage());
});
