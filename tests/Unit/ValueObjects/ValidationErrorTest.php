<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\ValueObjects;

use Cline\JsonSchema\ValueObjects\ValidationError;

use function array_keys;
use function expect;
use function explode;
use function it;
use function json_encode;
use function mb_strlen;
use function serialize;
use function str_repeat;
use function unserialize;

// ============================================================================
// Constructor Tests
// ============================================================================

it('creates validation error with all required fields', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email format', 'format');
    expect($error->path)->toBe('/user/email')
        ->and($error->message)->toBe('Invalid email format')
        ->and($error->keyword)->toBe('format');
});

it('creates validation error with root path', function (): void {
    $error = new ValidationError('/', 'Root validation failed', 'type');
    expect($error->path)->toBe('/')
        ->and($error->message)->toBe('Root validation failed')
        ->and($error->keyword)->toBe('type');
});

it('creates validation error with nested path', function (): void {
    $error = new ValidationError('/user/address/zipCode', 'Invalid zip code', 'pattern');
    expect($error->path)->toBe('/user/address/zipCode')
        ->and($error->message)->toBe('Invalid zip code')
        ->and($error->keyword)->toBe('pattern');
});

it('creates validation error with array index in path', function (): void {
    $error = new ValidationError('/users/0/email', 'Email required', 'required');
    expect($error->path)->toBe('/users/0/email')
        ->and($error->message)->toBe('Email required')
        ->and($error->keyword)->toBe('required');
});

it('creates validation error with empty path', function (): void {
    $error = new ValidationError('', 'Schema validation failed', 'schema');
    expect($error->path)->toBe('')
        ->and($error->message)->toBe('Schema validation failed')
        ->and($error->keyword)->toBe('schema');
});

it('creates validation error with empty message', function (): void {
    $error = new ValidationError('/field', '', 'type');
    expect($error->path)->toBe('/field')
        ->and($error->message)->toBe('')
        ->and($error->keyword)->toBe('type');
});

it('creates validation error with long message', function (): void {
    $message = str_repeat('This is a very long validation error message. ', 10);
    $error = new ValidationError('/field', $message, 'custom');
    expect($error->message)->toBe($message)
        ->and(mb_strlen($error->message))->toBeGreaterThan(100);
});

it('creates validation error with unicode in message', function (): void {
    $error = new ValidationError('/name', 'Invalid characters: é, ñ, ü, 🚀', 'pattern');
    expect($error->message)->toBe('Invalid characters: é, ñ, ü, 🚀');
});

it('creates validation error with special characters in path', function (): void {
    $error = new ValidationError('/user/$ref', 'Invalid reference', 'ref');
    expect($error->path)->toBe('/user/$ref');
});

// ============================================================================
// keyword() Method Tests
// ============================================================================

it('returns keyword via method', function (): void {
    $error = new ValidationError('/field', 'Error message', 'type');
    expect($error->keyword())->toBe('type');
});

it('keyword method returns same value as keyword property', function (): void {
    $error = new ValidationError('/field', 'Error message', 'required');
    expect($error->keyword())->toBe($error->keyword)
        ->and($error->keyword())->toBe('required');
});

it('keyword method returns minLength keyword', function (): void {
    $error = new ValidationError('/name', 'String too short', 'minLength');
    expect($error->keyword())->toBe('minLength');
});

it('keyword method returns maxLength keyword', function (): void {
    $error = new ValidationError('/name', 'String too long', 'maxLength');
    expect($error->keyword())->toBe('maxLength');
});

it('keyword method returns pattern keyword', function (): void {
    $error = new ValidationError('/email', 'Does not match pattern', 'pattern');
    expect($error->keyword())->toBe('pattern');
});

it('keyword method returns format keyword', function (): void {
    $error = new ValidationError('/date', 'Invalid date format', 'format');
    expect($error->keyword())->toBe('format');
});

it('keyword method returns enum keyword', function (): void {
    $error = new ValidationError('/status', 'Value not in enum', 'enum');
    expect($error->keyword())->toBe('enum');
});

it('keyword method returns const keyword', function (): void {
    $error = new ValidationError('/version', 'Does not match const', 'const');
    expect($error->keyword())->toBe('const');
});

it('keyword method returns minimum keyword', function (): void {
    $error = new ValidationError('/age', 'Value below minimum', 'minimum');
    expect($error->keyword())->toBe('minimum');
});

it('keyword method returns maximum keyword', function (): void {
    $error = new ValidationError('/age', 'Value above maximum', 'maximum');
    expect($error->keyword())->toBe('maximum');
});

it('keyword method returns multipleOf keyword', function (): void {
    $error = new ValidationError('/quantity', 'Not a multiple of specified value', 'multipleOf');
    expect($error->keyword())->toBe('multipleOf');
});

it('keyword method returns properties keyword', function (): void {
    $error = new ValidationError('/user', 'Invalid properties', 'properties');
    expect($error->keyword())->toBe('properties');
});

it('keyword method returns additionalProperties keyword', function (): void {
    $error = new ValidationError('/user', 'Additional properties not allowed', 'additionalProperties');
    expect($error->keyword())->toBe('additionalProperties');
});

it('keyword method returns custom keyword', function (): void {
    $error = new ValidationError('/field', 'Custom validation failed', 'customValidator');
    expect($error->keyword())->toBe('customValidator');
});

// ============================================================================
// message() Method Tests
// ============================================================================

it('returns message via method', function (): void {
    $error = new ValidationError('/field', 'This is an error message', 'type');
    expect($error->message())->toBe('This is an error message');
});

it('message method returns same value as message property', function (): void {
    $error = new ValidationError('/field', 'Error occurred', 'required');
    expect($error->message())->toBe($error->message)
        ->and($error->message())->toBe('Error occurred');
});

it('message method returns type mismatch message', function (): void {
    $msg = 'Expected string, got integer';
    $error = new ValidationError('/field', $msg, 'type');
    expect($error->message())->toBe($msg);
});

it('message method returns required field message', function (): void {
    $msg = 'Field is required';
    $error = new ValidationError('/email', $msg, 'required');
    expect($error->message())->toBe($msg);
});

it('message method returns pattern mismatch message', function (): void {
    $msg = 'Value does not match pattern ^[a-z]+$';
    $error = new ValidationError('/username', $msg, 'pattern');
    expect($error->message())->toBe($msg);
});

it('message method returns format validation message', function (): void {
    $msg = 'Value is not a valid email address';
    $error = new ValidationError('/contact', $msg, 'format');
    expect($error->message())->toBe($msg);
});

it('message method returns length constraint message', function (): void {
    $msg = 'String must be at least 5 characters long';
    $error = new ValidationError('/password', $msg, 'minLength');
    expect($error->message())->toBe($msg);
});

it('message method returns numeric constraint message', function (): void {
    $msg = 'Value must be greater than or equal to 0';
    $error = new ValidationError('/age', $msg, 'minimum');
    expect($error->message())->toBe($msg);
});

it('message method returns enum validation message', function (): void {
    $msg = 'Value must be one of: red, green, blue';
    $error = new ValidationError('/color', $msg, 'enum');
    expect($error->message())->toBe($msg);
});

it('message method preserves newlines and formatting', function (): void {
    $msg = "Line 1\nLine 2\nLine 3";
    $error = new ValidationError('/field', $msg, 'custom');
    expect($error->message())->toBe($msg);
});

it('message method preserves special characters', function (): void {
    $msg = 'Value must match: [a-z]{2,10}';
    $error = new ValidationError('/field', $msg, 'pattern');
    expect($error->message())->toBe($msg);
});

// ============================================================================
// path() Method Tests
// ============================================================================

it('returns path via method', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email', 'format');
    expect($error->path())->toBe('/user/email');
});

it('path method returns same value as path property', function (): void {
    $error = new ValidationError('/user/name', 'Name required', 'required');
    expect($error->path())->toBe($error->path)
        ->and($error->path())->toBe('/user/name');
});

it('path method returns root path', function (): void {
    $error = new ValidationError('/', 'Root error', 'type');
    expect($error->path())->toBe('/');
});

it('path method returns nested object path', function (): void {
    $error = new ValidationError('/user/profile/bio', 'Bio too long', 'maxLength');
    expect($error->path())->toBe('/user/profile/bio');
});

it('path method returns array element path', function (): void {
    $error = new ValidationError('/items/3/price', 'Invalid price', 'type');
    expect($error->path())->toBe('/items/3/price');
});

it('path method returns complex nested path', function (): void {
    $error = new ValidationError('/data/users/0/addresses/1/zipCode', 'Invalid zip', 'pattern');
    expect($error->path())->toBe('/data/users/0/addresses/1/zipCode');
});

it('path method returns path with property names containing slashes', function (): void {
    $error = new ValidationError('/properties/user/name', 'Error', 'type');
    expect($error->path())->toBe('/properties/user/name');
});

it('path method returns JSON pointer format path', function (): void {
    $error = new ValidationError('/foo/bar/baz', 'Error', 'type');
    expect($error->path())->toBe('/foo/bar/baz')
        ->and($error->path())->toStartWith('/');
});

it('path method returns dot notation path', function (): void {
    // Some validators use dot notation instead of JSON pointer
    $error = new ValidationError('user.email', 'Invalid', 'format');
    expect($error->path())->toBe('user.email');
});

it('path method returns path with special characters', function (): void {
    $error = new ValidationError('/user/$id', 'Invalid ID', 'type');
    expect($error->path())->toBe('/user/$id');
});

// ============================================================================
// toArray() Method Tests
// ============================================================================

it('converts error to array with all fields', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email format', 'format');
    $array = $error->toArray();
    expect($array)->toBe([
        'path' => '/user/email',
        'message' => 'Invalid email format',
        'keyword' => 'format',
    ]);
});

it('converts error with empty values to array', function (): void {
    $error = new ValidationError('', '', '');
    $array = $error->toArray();
    expect($array)->toBe([
        'path' => '',
        'message' => '',
        'keyword' => '',
    ]);
});

it('array has correct keys', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $array = $error->toArray();
    expect($array)->toHaveKeys(['path', 'message', 'keyword'])
        ->and(array_keys($array))->toBe(['path', 'message', 'keyword']);
});

it('array has exactly three elements', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $array = $error->toArray();
    expect($array)->toHaveCount(3);
});

it('array preserves unicode characters', function (): void {
    $error = new ValidationError('/name', 'Invalid: é, ñ, ü, 🚀', 'pattern');
    $array = $error->toArray();
    expect($array['message'])->toBe('Invalid: é, ñ, ü, 🚀');
});

it('array can be JSON encoded', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email', 'format');
    $array = $error->toArray();
    $json = json_encode($array);
    expect($json)->toBeString()
        ->and($json)->toContain('"path"')
        ->and($json)->toContain('"message"')
        ->and($json)->toContain('"keyword"');
});

it('array preserves path structure', function (): void {
    $error = new ValidationError('/users/0/addresses/1/street', 'Required', 'required');
    $array = $error->toArray();
    expect($array['path'])->toBe('/users/0/addresses/1/street');
});

it('array preserves long messages', function (): void {
    $msg = str_repeat('Error: ', 50);
    $error = new ValidationError('/field', $msg, 'custom');
    $array = $error->toArray();
    expect($array['message'])->toBe($msg);
});

it('array can be serialized and unserialized', function (): void {
    $error = new ValidationError('/field', 'Error message', 'type');
    $array = $error->toArray();
    $serialized = serialize($array);
    $unserialized = unserialize($serialized);
    expect($unserialized)->toBe($array);
});

it('array matches original constructor values', function (): void {
    $path = '/user/profile/age';
    $message = 'Age must be a positive integer';
    $keyword = 'type';
    $error = new ValidationError($path, $message, $keyword);
    $array = $error->toArray();
    expect($array['path'])->toBe($path)
        ->and($array['message'])->toBe($message)
        ->and($array['keyword'])->toBe($keyword);
});

// ============================================================================
// Immutability Tests
// ============================================================================

it('is immutable via readonly properties', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    expect($error->path)->toBe('/field')
        ->and($error->message)->toBe('Error')
        ->and($error->keyword)->toBe('type');
    // Attempting to modify would cause a PHP error due to readonly
});

it('toArray returns new array each time', function (): void {
    $error = new ValidationError('/field', 'Error', 'type');
    $array1 = $error->toArray();
    $array2 = $error->toArray();
    expect($array1)->toBe($array2);
    // Arrays are returned by value, so same content
});

it('multiple method calls return same values', function (): void {
    $error = new ValidationError('/user/email', 'Invalid email', 'format');
    expect($error->path())->toBe($error->path())
        ->and($error->message())->toBe($error->message())
        ->and($error->keyword())->toBe($error->keyword());
});

// ============================================================================
// Real-world Validation Error Examples
// ============================================================================

it('represents type mismatch error', function (): void {
    $error = new ValidationError(
        '/user/age',
        'Expected integer but got string',
        'type',
    );
    expect($error->keyword())->toBe('type')
        ->and($error->path())->toBe('/user/age')
        ->and($error->message())->toContain('integer');
});

it('represents required field error', function (): void {
    $error = new ValidationError(
        '/user/email',
        'The property email is required',
        'required',
    );
    expect($error->keyword())->toBe('required')
        ->and($error->message())->toContain('required');
});

it('represents pattern validation error', function (): void {
    $error = new ValidationError(
        '/username',
        'String does not match pattern ^[a-zA-Z0-9_]+$',
        'pattern',
    );
    expect($error->keyword())->toBe('pattern')
        ->and($error->message())->toContain('pattern');
});

it('represents format validation error', function (): void {
    $error = new ValidationError(
        '/contact/email',
        'String is not a valid email address',
        'format',
    );
    expect($error->keyword())->toBe('format')
        ->and($error->path())->toContain('email');
});

it('represents minimum constraint error', function (): void {
    $error = new ValidationError(
        '/product/price',
        'Value must be greater than or equal to 0',
        'minimum',
    );
    expect($error->keyword())->toBe('minimum')
        ->and($error->message())->toContain('greater than');
});

it('represents maximum constraint error', function (): void {
    $error = new ValidationError(
        '/user/age',
        'Value must be less than or equal to 120',
        'maximum',
    );
    expect($error->keyword())->toBe('maximum')
        ->and($error->message())->toContain('less than');
});

it('represents minLength constraint error', function (): void {
    $error = new ValidationError(
        '/password',
        'String must be at least 8 characters long',
        'minLength',
    );
    expect($error->keyword())->toBe('minLength')
        ->and($error->message())->toContain('8 characters');
});

it('represents maxLength constraint error', function (): void {
    $error = new ValidationError(
        '/description',
        'String must not exceed 500 characters',
        'maxLength',
    );
    expect($error->keyword())->toBe('maxLength')
        ->and($error->message())->toContain('500');
});

it('represents enum validation error', function (): void {
    $error = new ValidationError(
        '/status',
        'Value must be one of: pending, active, completed',
        'enum',
    );
    expect($error->keyword())->toBe('enum')
        ->and($error->message())->toContain('pending, active, completed');
});

it('represents additional properties error', function (): void {
    $error = new ValidationError(
        '/user',
        'Additional properties are not allowed: extraField',
        'additionalProperties',
    );
    expect($error->keyword())->toBe('additionalProperties')
        ->and($error->message())->toContain('not allowed');
});

it('represents array validation error', function (): void {
    $error = new ValidationError(
        '/items/5',
        'Array item at index 5 is invalid',
        'items',
    );
    expect($error->path())->toContain('/5')
        ->and($error->keyword())->toBe('items');
});

it('represents nested object validation error', function (): void {
    $error = new ValidationError(
        '/user/address/zipCode',
        'Zip code must match pattern ^\d{5}$',
        'pattern',
    );
    expect($error->path())->toBe('/user/address/zipCode')
        ->and($error->message())->toContain('^\d{5}$');
});

// ============================================================================
// Edge Cases
// ============================================================================

it('handles very long paths', function (): void {
    $path = '/'.str_repeat('level/', 50).'field';
    $error = new ValidationError($path, 'Error', 'type');
    expect($error->path())->toBe($path);
});

it('handles paths with numbers only', function (): void {
    $error = new ValidationError('/0/1/2/3', 'Array nesting error', 'type');
    expect($error->path())->toBe('/0/1/2/3');
});

it('handles keywords with mixed case', function (): void {
    $error = new ValidationError('/field', 'Error', 'CustomKeyword');
    expect($error->keyword())->toBe('CustomKeyword');
});

it('handles multiline messages', function (): void {
    $message = "Error on line 1\nError on line 2\nError on line 3";
    $error = new ValidationError('/field', $message, 'custom');
    expect($error->message())->toContain("\n")
        ->and(explode("\n", $error->message()))->toHaveCount(3);
});

it('handles messages with JSON content', function (): void {
    $message = 'Expected: {"type":"object"}, got: {"type":"string"}';
    $error = new ValidationError('/field', $message, 'type');
    expect($error->message())->toContain('{"type":"object"}');
});

it('handles paths with encoded characters', function (): void {
    $error = new ValidationError('/user/name%20with%20spaces', 'Error', 'type');
    expect($error->path())->toBe('/user/name%20with%20spaces');
});

it('handles empty keyword', function (): void {
    $error = new ValidationError('/field', 'Generic error', '');
    expect($error->keyword())->toBe('');
});
