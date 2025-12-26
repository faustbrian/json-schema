<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\ValueObjects;

use Cline\JsonSchema\ValueObjects\ValidationError;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function array_keys;
use function expect;
use function it;
use function json_decode;
use function json_encode;
use function serialize;
use function str_repeat;
use function unserialize;

// ============================================================================
// Constructor Tests
// ============================================================================

it('creates valid result with no errors', function (): void {
    $result = new ValidationResult(true, []);
    expect($result->valid)->toBeTrue()
        ->and($result->errors)->toBe([]);
});

it('creates invalid result with errors', function (): void {
    $errors = [
        new ValidationError('/field', 'Error message', 'type'),
    ];
    $result = new ValidationResult(false, $errors);
    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toBe($errors);
});

it('creates valid result with empty errors by default', function (): void {
    $result = new ValidationResult(true);
    expect($result->valid)->toBeTrue()
        ->and($result->errors)->toBe([]);
});

it('creates invalid result with multiple errors', function (): void {
    $errors = [
        new ValidationError('/field1', 'Error 1', 'type'),
        new ValidationError('/field2', 'Error 2', 'required'),
        new ValidationError('/field3', 'Error 3', 'pattern'),
    ];
    $result = new ValidationResult(false, $errors);
    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toHaveCount(3);
});

// ============================================================================
// success() Factory Method Tests
// ============================================================================

it('creates successful result via factory method', function (): void {
    $result = ValidationResult::success();
    expect($result->valid)->toBeTrue()
        ->and($result->errors)->toBe([])
        ->and($result->errors)->toHaveCount(0);
});

it('success result is valid', function (): void {
    $result = ValidationResult::success();
    expect($result->isValid())->toBeTrue()
        ->and($result->isInvalid())->toBeFalse();
});

it('success result has no errors', function (): void {
    $result = ValidationResult::success();
    expect($result->getErrors())->toBeEmpty()
        ->and($result->errors())->toBeEmpty();
});

it('multiple success calls create independent instances', function (): void {
    $result1 = ValidationResult::success();
    $result2 = ValidationResult::success();
    expect($result1)->not->toBe($result2)
        ->and($result1->valid)->toBe($result2->valid)
        ->and($result1->errors)->toBe($result2->errors);
});

// ============================================================================
// failure() Factory Method Tests
// ============================================================================

it('creates failed result via factory method', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toBe($errors);
});

it('failure result is invalid', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->isValid())->toBeFalse()
        ->and($result->isInvalid())->toBeTrue();
});

it('failure result has errors', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->getErrors())->not->toBeEmpty()
        ->and($result->errors())->toHaveCount(1);
});

it('creates failed result with multiple errors', function (): void {
    $errors = [
        new ValidationError('/email', 'Invalid email', 'format'),
        new ValidationError('/age', 'Must be positive', 'minimum'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors)->toHaveCount(2)
        ->and($result->errors[0]->keyword)->toBe('format')
        ->and($result->errors[1]->keyword)->toBe('minimum');
});

it('creates failed result with empty errors array', function (): void {
    $result = ValidationResult::failure([]);
    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toBeEmpty();
});

it('failure with many errors preserves all', function (): void {
    $errors = [];

    for ($i = 0; $i < 10; ++$i) {
        $errors[] = new ValidationError('/field'.$i, 'Error '.$i, 'type');
    }

    $result = ValidationResult::failure($errors);
    expect($result->errors)->toHaveCount(10)
        ->and($result->errors[0]->path)->toBe('/field0')
        ->and($result->errors[9]->path)->toBe('/field9');
});

// ============================================================================
// isValid() Method Tests
// ============================================================================

it('isValid returns true for successful validation', function (): void {
    $result = ValidationResult::success();
    expect($result->isValid())->toBeTrue();
});

it('isValid returns false for failed validation', function (): void {
    $result = ValidationResult::failure([new ValidationError('/field', 'Error', 'type')]);
    expect($result->isValid())->toBeFalse();
});

it('isValid returns same as valid property', function (): void {
    $result1 = ValidationResult::success();
    $result2 = ValidationResult::failure([new ValidationError('/field', 'Error', 'type')]);
    expect($result1->isValid())->toBe($result1->valid)
        ->and($result2->isValid())->toBe($result2->valid);
});

it('isValid returns true for manually created valid result', function (): void {
    $result = new ValidationResult(true, []);
    expect($result->isValid())->toBeTrue();
});

it('isValid returns false for manually created invalid result', function (): void {
    $result = new ValidationResult(false, []);
    expect($result->isValid())->toBeFalse();
});

// ============================================================================
// isInvalid() Method Tests
// ============================================================================

it('isInvalid returns false for successful validation', function (): void {
    $result = ValidationResult::success();
    expect($result->isInvalid())->toBeFalse();
});

it('isInvalid returns true for failed validation', function (): void {
    $result = ValidationResult::failure([new ValidationError('/field', 'Error', 'type')]);
    expect($result->isInvalid())->toBeTrue();
});

it('isInvalid is opposite of isValid', function (): void {
    $result1 = ValidationResult::success();
    $result2 = ValidationResult::failure([new ValidationError('/field', 'Error', 'type')]);
    expect($result1->isInvalid())->toBe(!$result1->isValid())
        ->and($result2->isInvalid())->toBe(!$result2->isValid());
});

it('isInvalid returns true even with empty errors', function (): void {
    $result = new ValidationResult(false, []);
    expect($result->isInvalid())->toBeTrue()
        ->and($result->errors)->toBeEmpty();
});

it('isInvalid returns false for manually created valid result', function (): void {
    $result = new ValidationResult(true, []);
    expect($result->isInvalid())->toBeFalse();
});

it('isInvalid returns true for manually created invalid result', function (): void {
    $result = new ValidationResult(false, []);
    expect($result->isInvalid())->toBeTrue();
});

// ============================================================================
// getErrors() Method Tests
// ============================================================================

it('getErrors returns empty array for valid result', function (): void {
    $result = ValidationResult::success();
    expect($result->getErrors())->toBe([])
        ->and($result->getErrors())->toBeEmpty();
});

it('getErrors returns all errors for invalid result', function (): void {
    $errors = [
        new ValidationError('/field1', 'Error 1', 'type'),
        new ValidationError('/field2', 'Error 2', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->getErrors())->toBe($errors)
        ->and($result->getErrors())->toHaveCount(2);
});

it('getErrors returns same as errors property', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->getErrors())->toBe($result->errors);
});

it('getErrors preserves error order', function (): void {
    $errors = [
        new ValidationError('/a', 'Error A', 'type'),
        new ValidationError('/b', 'Error B', 'type'),
        new ValidationError('/c', 'Error C', 'type'),
    ];
    $result = ValidationResult::failure($errors);
    $retrieved = $result->getErrors();
    expect($retrieved[0]->path)->toBe('/a')
        ->and($retrieved[1]->path)->toBe('/b')
        ->and($retrieved[2]->path)->toBe('/c');
});

it('getErrors returns ValidationError instances', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->getErrors())->each->toBeInstanceOf(ValidationError::class);
});

// ============================================================================
// errors() Method Tests (Alias)
// ============================================================================

it('errors method returns same as getErrors', function (): void {
    $errors = [
        new ValidationError('/field1', 'Error 1', 'type'),
        new ValidationError('/field2', 'Error 2', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors())->toBe($result->getErrors());
});

it('errors method returns empty array for valid result', function (): void {
    $result = ValidationResult::success();
    expect($result->errors())->toBe([])
        ->and($result->errors())->toBeEmpty();
});

it('errors method returns all errors for invalid result', function (): void {
    $errors = [
        new ValidationError('/field1', 'Error 1', 'type'),
        new ValidationError('/field2', 'Error 2', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors())->toBe($errors)
        ->and($result->errors())->toHaveCount(2);
});

it('errors method and getErrors return identical values', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    expect($result->errors())->toBe($result->getErrors())
        ->and($result->errors())->toBe($result->errors);
});

// ============================================================================
// toArray() Method Tests
// ============================================================================

it('converts valid result to array', function (): void {
    $result = ValidationResult::success();
    $array = $result->toArray();
    expect($array)->toBe([
        'valid' => true,
        'errors' => [],
    ]);
});

it('converts invalid result with one error to array', function (): void {
    $error = new ValidationError('/field', 'Error message', 'type');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    expect($array['valid'])->toBeFalse()
        ->and($array['errors'])->toHaveCount(1)
        ->and($array['errors'][0])->toBe([
            'path' => '/field',
            'message' => 'Error message',
            'keyword' => 'type',
        ]);
});

it('converts invalid result with multiple errors to array', function (): void {
    $errors = [
        new ValidationError('/email', 'Invalid email', 'format'),
        new ValidationError('/age', 'Must be positive', 'minimum'),
    ];
    $result = ValidationResult::failure($errors);
    $array = $result->toArray();
    expect($array['valid'])->toBeFalse()
        ->and($array['errors'])->toHaveCount(2)
        ->and($array['errors'][0]['path'])->toBe('/email')
        ->and($array['errors'][1]['path'])->toBe('/age');
});

it('toArray has correct structure', function (): void {
    $result = ValidationResult::success();
    $array = $result->toArray();
    expect($array)->toHaveKeys(['valid', 'errors'])
        ->and(array_keys($array))->toBe(['valid', 'errors']);
});

it('toArray errors are arrays not objects', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    expect($array['errors'][0])->toBeArray()
        ->and($array['errors'][0])->not->toBeInstanceOf(ValidationError::class);
});

it('toArray can be JSON encoded', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    $json = json_encode($array);
    expect($json)->toBeString()
        ->and($json)->toContain('"valid"')
        ->and($json)->toContain('"errors"');
});

it('toArray preserves error details', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email format', 'format');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    expect($array['errors'][0])->toBe([
        'path' => '/user/email',
        'message' => 'Invalid email format',
        'keyword' => 'format',
    ]);
});

it('toArray handles empty errors for invalid result', function (): void {
    $result = new ValidationResult(false, []);
    $array = $result->toArray();
    expect($array)->toBe([
        'valid' => false,
        'errors' => [],
    ]);
});

it('toArray handles many errors', function (): void {
    $errors = [];

    for ($i = 0; $i < 5; ++$i) {
        $errors[] = new ValidationError('/field'.$i, 'Error '.$i, 'type');
    }

    $result = ValidationResult::failure($errors);
    $array = $result->toArray();
    expect($array['errors'])->toHaveCount(5)
        ->and($array['errors'][0]['path'])->toBe('/field0')
        ->and($array['errors'][4]['path'])->toBe('/field4');
});

it('toArray preserves error order', function (): void {
    $errors = [
        new ValidationError('/z', 'Error Z', 'type'),
        new ValidationError('/a', 'Error A', 'type'),
        new ValidationError('/m', 'Error M', 'type'),
    ];
    $result = ValidationResult::failure($errors);
    $array = $result->toArray();
    expect($array['errors'][0]['path'])->toBe('/z')
        ->and($array['errors'][1]['path'])->toBe('/a')
        ->and($array['errors'][2]['path'])->toBe('/m');
});

// ============================================================================
// Immutability Tests
// ============================================================================

it('is immutable via readonly properties', function (): void {
    $result = ValidationResult::success();
    expect($result->valid)->toBeTrue()
        ->and($result->errors)->toBe([]);
    // Attempting to modify would cause a PHP error due to readonly
});

it('toArray returns new array each time', function (): void {
    $result = ValidationResult::success();
    $array1 = $result->toArray();
    $array2 = $result->toArray();
    expect($array1)->toBe($array2);
    // Arrays are returned by value, so same content
});

it('getErrors returns same array reference', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);
    $errors1 = $result->getErrors();
    $errors2 = $result->getErrors();
    expect($errors1)->toBe($errors2);
});

it('multiple method calls return consistent values', function (): void {
    $result = ValidationResult::success();
    expect($result->isValid())->toBe($result->isValid())
        ->and($result->isInvalid())->toBe($result->isInvalid())
        ->and($result->getErrors())->toBe($result->getErrors())
        ->and($result->errors())->toBe($result->errors());
});

// ============================================================================
// Real-world Validation Scenarios
// ============================================================================

it('represents successful user validation', function (): void {
    $result = ValidationResult::success();
    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('represents failed user validation with multiple field errors', function (): void {
    $errors = [
        new ValidationError('/email', 'Invalid email format', 'format'),
        new ValidationError('/age', 'Must be at least 18', 'minimum'),
        new ValidationError('/username', 'Required field', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->isInvalid())->toBeTrue()
        ->and($result->getErrors())->toHaveCount(3);
});

it('represents schema validation with nested object errors', function (): void {
    $errors = [
        new ValidationError('/user/profile/email', 'Invalid email', 'format'),
        new ValidationError('/user/address/zipCode', 'Invalid zip code', 'pattern'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors[0]->path)->toContain('/user/profile')
        ->and($result->errors[1]->path)->toContain('/user/address');
});

it('represents array validation with item errors', function (): void {
    $errors = [
        new ValidationError('/items/0/price', 'Must be positive', 'minimum'),
        new ValidationError('/items/2/name', 'Required field', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors)->toHaveCount(2)
        ->and($result->errors[0]->path)->toContain('/items/0')
        ->and($result->errors[1]->path)->toContain('/items/2');
});

it('represents complex schema validation with mixed errors', function (): void {
    $errors = [
        new ValidationError('/', 'Must be object', 'type'),
        new ValidationError('/id', 'Required field', 'required'),
        new ValidationError('/email', 'Invalid format', 'format'),
        new ValidationError('/age', 'Below minimum', 'minimum'),
        new ValidationError('/status', 'Not in enum', 'enum'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->getErrors())->toHaveCount(5)
        ->and($result->isInvalid())->toBeTrue();
});

it('represents validation of required fields only', function (): void {
    $errors = [
        new ValidationError('/name', 'Required field', 'required'),
        new ValidationError('/email', 'Required field', 'required'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors)->each->toBeInstanceOf(ValidationError::class)
        ->and($result->errors[0]->keyword)->toBe('required')
        ->and($result->errors[1]->keyword)->toBe('required');
});

it('represents validation of format constraints', function (): void {
    $errors = [
        new ValidationError('/email', 'Not valid email', 'format'),
        new ValidationError('/date', 'Not valid date', 'format'),
        new ValidationError('/url', 'Not valid URL', 'format'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors[0]->keyword)->toBe('format')
        ->and($result->errors[1]->keyword)->toBe('format')
        ->and($result->errors[2]->keyword)->toBe('format');
});

it('represents validation with pattern errors', function (): void {
    $errors = [
        new ValidationError('/username', 'Does not match ^[a-zA-Z0-9]+$', 'pattern'),
        new ValidationError('/zipCode', 'Does not match ^\d{5}$', 'pattern'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors[0]->keyword)->toBe('pattern')
        ->and($result->errors[1]->keyword)->toBe('pattern');
});

// ============================================================================
// Edge Cases
// ============================================================================

it('handles result with 100 errors', function (): void {
    $errors = [];

    for ($i = 0; $i < 100; ++$i) {
        $errors[] = new ValidationError('/field'.$i, 'Error '.$i, 'type');
    }

    $result = ValidationResult::failure($errors);
    expect($result->errors)->toHaveCount(100)
        ->and($result->isInvalid())->toBeTrue();
});

it('handles errors with very long messages', function (): void {
    $longMessage = str_repeat('This is a very long error message. ', 50);
    $error = new ValidationError('/field', $longMessage, 'custom');
    $result = ValidationResult::failure([$error]);
    expect($result->errors[0]->message)->toBe($longMessage);
});

it('handles errors with unicode characters', function (): void {
    $error = new ValidationError('/name', 'Invalid characters: é, ñ, ü, 🚀', 'pattern');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    expect($array['errors'][0]['message'])->toContain('🚀');
});

it('handles errors with deeply nested paths', function (): void {
    $path = '/level1/level2/level3/level4/level5/level6/field';
    $error = new ValidationError($path, 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    expect($result->errors[0]->path)->toBe($path);
});

it('handles result with mixed error keywords', function (): void {
    $errors = [
        new ValidationError('/a', 'Error', 'type'),
        new ValidationError('/b', 'Error', 'required'),
        new ValidationError('/c', 'Error', 'pattern'),
        new ValidationError('/d', 'Error', 'format'),
        new ValidationError('/e', 'Error', 'minimum'),
        new ValidationError('/f', 'Error', 'maximum'),
    ];
    $result = ValidationResult::failure($errors);
    expect($result->errors)->toHaveCount(6)
        ->and($result->errors[0]->keyword)->toBe('type')
        ->and($result->errors[5]->keyword)->toBe('maximum');
});

it('can serialize and unserialize toArray result', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    $serialized = serialize($array);
    $unserialized = unserialize($serialized);
    expect($unserialized)->toBe($array);
});

it('toArray result can be JSON encoded and decoded', function (): void {
    $errors = [
        new ValidationError('/email', 'Invalid email', 'format'),
        new ValidationError('/age', 'Too young', 'minimum'),
    ];
    $result = ValidationResult::failure($errors);
    $array = $result->toArray();
    $json = json_encode($array);
    $decoded = json_decode($json, true);
    expect($decoded)->toBe($array);
});

// ============================================================================
// Comparison and Equality Tests
// ============================================================================

it('two success results have same values', function (): void {
    $result1 = ValidationResult::success();
    $result2 = ValidationResult::success();
    expect($result1->valid)->toBe($result2->valid)
        ->and($result1->errors)->toBe($result2->errors);
});

it('two failure results with same errors have same values', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result1 = ValidationResult::failure($errors);
    $result2 = ValidationResult::failure($errors);
    expect($result1->valid)->toBe($result2->valid)
        ->and($result1->errors)->toBe($result2->errors);
});

it('success and failure results are different', function (): void {
    $result1 = ValidationResult::success();
    $result2 = ValidationResult::failure([new ValidationError('/field', 'Error', 'type')]);
    expect($result1->valid)->not->toBe($result2->valid)
        ->and($result1->isValid())->not->toBe($result2->isValid());
});

// ============================================================================
// Method Chaining and Usage Patterns
// ============================================================================

it('can check validity and get errors in sequence', function (): void {
    $errors = [new ValidationError('/field', 'Error', 'type')];
    $result = ValidationResult::failure($errors);

    if (!$result->isInvalid()) {
        return;
    }

    $retrievedErrors = $result->getErrors();
    expect($retrievedErrors)->toHaveCount(1);
});

it('can convert to array and access nested properties', function (): void {
    $error = new ValidationError('/user/email', 'Invalid', 'format');
    $result = ValidationResult::failure([$error]);
    $array = $result->toArray();
    expect($array['valid'])->toBeFalse()
        ->and($array['errors'][0]['path'])->toBe('/user/email');
});

it('supports typical validation workflow', function (): void {
    // Simulate a validation workflow
    $result = ValidationResult::success();

    if ($result->isValid()) {
        expect($result->getErrors())->toBeEmpty();
        // Proceed with valid data
    } else {
        // Handle errors (this branch shouldn't run)
        expect(true)->toBeFalse();
    }
});

it('supports error reporting workflow', function (): void {
    $errors = [
        new ValidationError('/email', 'Invalid email', 'format'),
        new ValidationError('/age', 'Too young', 'minimum'),
    ];
    $result = ValidationResult::failure($errors);

    if (!$result->isInvalid()) {
        return;
    }

    $errorList = $result->errors();
    expect($errorList)->toHaveCount(2);

    foreach ($errorList as $error) {
        expect($error)->toBeInstanceOf(ValidationError::class);
    }
});
