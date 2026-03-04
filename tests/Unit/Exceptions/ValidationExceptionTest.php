<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions;

use Cline\JsonSchema\Exceptions\JsonSchemaException;
use Cline\JsonSchema\Exceptions\ValidationException;
use Cline\JsonSchema\ValueObjects\ValidationError;
use Cline\JsonSchema\ValueObjects\ValidationResult;
use ReflectionClass;
use RuntimeException;

use function expect;
use function it;

it('creates exception with single validation error', function (): void {
    $error = new ValidationError('/name', 'Field is required', 'required');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception->getMessage())->toBe('Validation failed: [/name] Field is required');
});

it('creates exception with multiple validation errors', function (): void {
    $errors = [
        new ValidationError('/name', 'Field is required', 'required'),
        new ValidationError('/age', 'Must be at least 18', 'minimum'),
        new ValidationError('/email', 'Invalid format', 'format'),
    ];
    $result = ValidationResult::failure($errors);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toBe('Validation failed: [/name] Field is required, [/age] Must be at least 18, [/email] Invalid format');
});

it('creates exception with empty path', function (): void {
    $error = new ValidationError('', 'Root validation failed', 'type');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toBe('Validation failed: [] Root validation failed');
});

it('creates exception with nested path', function (): void {
    $error = new ValidationError('/user/address/zipCode', 'Invalid zip code format', 'pattern');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toBe('Validation failed: [/user/address/zipCode] Invalid zip code format');
});

it('creates exception with array index in path', function (): void {
    $error = new ValidationError('/items/0/name', 'Item name is required', 'required');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toBe('Validation failed: [/items/0/name] Item name is required');
});

it('stores validation result as public property', function (): void {
    $error = new ValidationError('/name', 'Field is required', 'required');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->result)->toBe($result)
        ->and($exception->result)->toBeInstanceOf(ValidationResult::class);
});

it('provides getResult method', function (): void {
    $error = new ValidationError('/name', 'Field is required', 'required');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getResult())->toBe($result)
        ->and($exception->getResult())->toBeInstanceOf(ValidationResult::class);
});

it('result property matches getResult method', function (): void {
    $error = new ValidationError('/name', 'Field is required', 'required');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->result)->toBe($exception->getResult());
});

it('preserves all error details in result', function (): void {
    $errors = [
        new ValidationError('/name', 'Field is required', 'required'),
        new ValidationError('/age', 'Must be numeric', 'type'),
    ];
    $result = ValidationResult::failure($errors);
    $exception = new ValidationException($result);

    expect($exception->getResult()->getErrors())->toBe($errors)
        ->and($exception->getResult()->getErrors())->toHaveCount(2);
});

it('extends JsonSchemaException', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception)->toBeInstanceOf(JsonSchemaException::class);
});

it('extends RuntimeException', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('can be thrown and caught', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);

    expect(fn (): mixed => throw new ValidationException($result))
        ->toThrow(ValidationException::class);
});

it('can be caught as JsonSchemaException', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);

    expect(fn (): mixed => throw new ValidationException($result))
        ->toThrow(JsonSchemaException::class);
});

it('can be caught as RuntimeException', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);

    expect(fn (): mixed => throw new ValidationException($result))
        ->toThrow(RuntimeException::class);
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(ValidationException::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has correct exception code and previous exception', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

it('creates distinct exception instances', function (): void {
    $error1 = new ValidationError('/name', 'Error 1', 'type');
    $result1 = ValidationResult::failure([$error1]);
    $exception1 = new ValidationException($result1);

    $error2 = new ValidationError('/email', 'Error 2', 'format');
    $result2 = ValidationResult::failure([$error2]);
    $exception2 = new ValidationException($result2);

    expect($exception1)->not->toBe($exception2)
        ->and($exception1->getMessage())->not->toBe($exception2->getMessage());
});

it('formats message with different error keywords', function (): void {
    $errors = [
        new ValidationError('/type', 'Invalid type', 'type'),
        new ValidationError('/minLength', 'Too short', 'minLength'),
        new ValidationError('/pattern', 'Pattern mismatch', 'pattern'),
        new ValidationError('/required', 'Missing field', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toContain('[/type] Invalid type')
        ->and($exception->getMessage())->toContain('[/minLength] Too short')
        ->and($exception->getMessage())->toContain('[/pattern] Pattern mismatch')
        ->and($exception->getMessage())->toContain('[/required] Missing field');
});

it('handles special characters in error messages', function (): void {
    $error = new ValidationError('/name', "Value contains 'quotes' and \"double quotes\"", 'pattern');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toContain("Value contains 'quotes' and \"double quotes\"");
});

it('handles unicode characters in error messages', function (): void {
    $error = new ValidationError('/description', 'Must not contain emoji 😀', 'pattern');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getMessage())->toContain('Must not contain emoji 😀');
});

it('result is readonly property', function (): void {
    $reflection = new ReflectionClass(ValidationException::class);
    $property = $reflection->getProperty('result');

    expect($property->isReadOnly())->toBeTrue();
});

it('accesses result errors through getResult', function (): void {
    $errors = [
        new ValidationError('/field1', 'Error 1', 'type'),
        new ValidationError('/field2', 'Error 2', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    $exception = new ValidationException($result);

    expect($exception->getResult()->errors())->toHaveCount(2)
        ->and($exception->getResult()->errors()[0]->path)->toBe('/field1')
        ->and($exception->getResult()->errors()[1]->path)->toBe('/field2');
});

it('maintains validation result validity flag', function (): void {
    $error = new ValidationError('/name', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $exception = new ValidationException($result);

    expect($exception->getResult()->isValid())->toBeFalse()
        ->and($exception->getResult()->isInvalid())->toBeTrue();
});
