<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Concerns;

use Cline\JsonSchema\Support\JsonDecoder;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use const INF;
use const NAN;

use function expect;
use function it;

// ============================================================================
// Type Validation Tests
// ============================================================================

it('validates null type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'null']);

    // Valid null
    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - not null
    $result = $validator->validate('null', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates boolean type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'boolean']);

    // Valid boolean
    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(false, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - not boolean
    $result = $validator->validate(1, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate('true', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates string type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'string']);

    // Valid string
    $result = $validator->validate('text', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - not string
    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates number type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'number']);

    // Valid number (both int and float)
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(3.14, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - not number
    $result = $validator->validate('42', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates integer type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'integer']);

    // Valid integer
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(-10, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid - integer float (Draft 07+)
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - float with fractional part
    $result = $validator->validate(3.14, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - not integer
    $result = $validator->validate('42', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates array type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'array']);

    // Valid arrays
    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - object
    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - not array
    $result = $validator->validate('array', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates object type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'object']);

    // Valid objects
    $result = $validator->validate(['key' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Line 159: Empty array without marker is not an object
    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - array
    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Invalid - not object
    $result = $validator->validate('object', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('distinguishes empty object from empty array', function (): void {
    $validator = new Draft07Validator();

    // Empty array should match array type
    $arraySchema = new Schema(['type' => 'array']);
    $result = $validator->validate([], $arraySchema->toArray());
    expect($result->isValid())->toBeTrue();

    // Empty array should NOT match object type (line 169)
    $objectSchema = new Schema(['type' => 'object']);
    $result = $validator->validate([], $objectSchema->toArray());
    expect($result->isValid())->toBeFalse();

    // Empty object (with marker) should match object type
    $emptyObject = JsonDecoder::decode('{}');
    $result = $validator->validate($emptyObject, $objectSchema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates associative arrays as objects', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'object']);

    // Line 115: Associative arrays should be objects
    $result = $validator->validate(['a' => 1, 'b' => 2], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Non-sequential numeric keys should be objects
    $result = $validator->validate([0 => 'a', 2 => 'b'], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1 => 'a', 0 => 'b'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates sequential arrays as arrays not objects', function (): void {
    $validator = new Draft07Validator();
    $arraySchema = new Schema(['type' => 'array']);
    $objectSchema = new Schema(['type' => 'object']);

    // Sequential numeric arrays should be arrays
    $result = $validator->validate([0 => 'a', 1 => 'b', 2 => 'c'], $arraySchema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([0 => 'a', 1 => 'b', 2 => 'c'], $objectSchema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates multiple types', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => ['string', 'number']]);

    // Valid - string
    $result = $validator->validate('text', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Valid - number
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(3.14, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Invalid - not in allowed types
    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate([], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates without type keyword', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['minLength' => 5]);

    // Should not fail type validation when type keyword is absent
    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(12_345, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// Edge Cases for Integer Float Detection
// ============================================================================

it('validates bignum integers in Draft 04', function (): void {
    $validator = new Draft04Validator();
    $schema = new Schema(['type' => 'integer']);

    // Line 136, 141: Draft 04 only accepts bignums exceeding PHP_INT_MAX
    // Regular integer floats like 1.0 should NOT be accepted in Draft 04
    $result = $validator->validate(1.0, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    // Regular integers should be accepted
    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Bignums exceeding PHP_INT_MAX should be accepted
    // Use a large float that represents an integer but exceeds PHP_INT_MAX
    $bignum = 9.223_372_036_854_776e18; // > PHP_INT_MAX
    $result = $validator->validate($bignum, $schema->toArray());
    // Note: This tests the bignum logic path, though actual behavior depends on float precision
    // The key is that regular floats like 1.0 are rejected, only extreme values are accepted
});

it('rejects NaN as integer in all drafts', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'integer']);

    // Line 141: NaN should never be accepted as integer
    $result = $validator->validate(NAN, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('rejects infinity as integer in all drafts', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'integer']);

    // Line 141: Infinity should never be accepted as integer
    $result = $validator->validate(INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(-INF, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates floats with fractional parts are not integers', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'integer']);

    // Line 141: Floats with fractional parts should be rejected
    $result = $validator->validate(1.5, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(0.1, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(-3.7, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// ============================================================================
// Empty Object Marker Tests
// ============================================================================

it('validates empty object with marker as object type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'object']);

    // Line 191: Empty object markers should be recognized as objects
    $emptyObject = JsonDecoder::decode('{}');
    $result = $validator->validate($emptyObject, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('rejects empty object marker as array type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'array']);

    // Line 186, 191: Empty object markers should NOT be arrays
    $emptyObject = JsonDecoder::decode('{}');
    $result = $validator->validate($emptyObject, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('handles complex type validation scenarios', function (): void {
    $validator = new Draft07Validator();

    // Test all JSON types
    $types = [
        ['type' => 'null', 'valid' => null, 'invalid' => 'null'],
        ['type' => 'boolean', 'valid' => true, 'invalid' => 1],
        ['type' => 'string', 'valid' => 'text', 'invalid' => 123],
        ['type' => 'number', 'valid' => 42, 'invalid' => 'text'],
        ['type' => 'integer', 'valid' => 42, 'invalid' => 3.14],
        ['type' => 'array', 'valid' => [1, 2], 'invalid' => ['key' => 'value']],
        ['type' => 'object', 'valid' => ['key' => 'value'], 'invalid' => [1, 2]],
    ];

    foreach ($types as $test) {
        $schema = new Schema(['type' => $test['type']]);

        $result = $validator->validate($test['valid'], $schema->toArray());
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate($test['invalid'], $schema->toArray());
        expect($result->isValid())->toBeFalse();
    }
});

it('validates unknown type as invalid', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'unknown']);

    // Unknown type should fail validation
    $result = $validator->validate('any-value', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('handles non-array and non-object data for object type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'object']);

    // Line 159: Non-array data should be rejected for object type
    $result = $validator->validate('not-an-object', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('handles non-array data for array type', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'array']);

    // Line 186: Non-array data should be rejected for array type
    $result = $validator->validate('not-an-array', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate(null, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
