<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\JsonDecoder;
use stdClass;

use function expect;
use function it;

// ============================================================================
// decode() Method Tests - Happy Path
// ============================================================================

it('decodes empty object as marker array', function (): void {
    $result = JsonDecoder::decode('{}');
    expect($result)->toBe(['__EMPTY_JSON_OBJECT__' => true]);
});

it('decodes empty array as empty array', function (): void {
    $result = JsonDecoder::decode('[]');
    expect($result)->toBe([]);
});

it('decodes object with properties', function (): void {
    $result = JsonDecoder::decode('{"name":"John","age":30}');
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
});

it('decodes array with values', function (): void {
    $result = JsonDecoder::decode('[1,2,3]');
    expect($result)->toBe([1, 2, 3]);
});

it('decodes string value', function (): void {
    $result = JsonDecoder::decode('"hello"');
    expect($result)->toBe('hello');
});

it('decodes number value', function (): void {
    $result = JsonDecoder::decode('42');
    expect($result)->toBe(42);
});

it('decodes float value', function (): void {
    $result = JsonDecoder::decode('3.14');
    expect($result)->toBe(3.14);
});

it('decodes boolean true', function (): void {
    $result = JsonDecoder::decode('true');
    expect($result)->toBeTrue();
});

it('decodes boolean false', function (): void {
    $result = JsonDecoder::decode('false');
    expect($result)->toBeFalse();
});

it('decodes null value', function (): void {
    $result = JsonDecoder::decode('null');
    expect($result)->toBeNull();
});

// ============================================================================
// decode() Method Tests - Nested Structures
// ============================================================================

it('decodes nested object with empty object', function (): void {
    $result = JsonDecoder::decode('{"data":{}}');
    expect($result)->toBe(['data' => ['__EMPTY_JSON_OBJECT__' => true]]);
});

it('decodes nested object with empty array', function (): void {
    $result = JsonDecoder::decode('{"items":[]}');
    expect($result)->toBe(['items' => []]);
});

it('decodes array containing empty objects', function (): void {
    $result = JsonDecoder::decode('[{},{}]');
    expect($result)->toBe([
        ['__EMPTY_JSON_OBJECT__' => true],
        ['__EMPTY_JSON_OBJECT__' => true],
    ]);
});

it('decodes array containing empty arrays', function (): void {
    $result = JsonDecoder::decode('[[],[]]');
    expect($result)->toBe([[], []]);
});

it('decodes deeply nested empty objects', function (): void {
    $result = JsonDecoder::decode('{"a":{"b":{"c":{}}}}');
    expect($result)->toBe([
        'a' => [
            'b' => [
                'c' => ['__EMPTY_JSON_OBJECT__' => true],
            ],
        ],
    ]);
});

it('decodes mixed nested structures', function (): void {
    $result = JsonDecoder::decode('{"obj":{},"arr":[],"nested":{"obj2":{}}}');
    expect($result)->toBe([
        'obj' => ['__EMPTY_JSON_OBJECT__' => true],
        'arr' => [],
        'nested' => ['obj2' => ['__EMPTY_JSON_OBJECT__' => true]],
    ]);
});

it('decodes array with mixed empty structures', function (): void {
    $result = JsonDecoder::decode('[{},[],"value",123]');
    expect($result)->toBe([
        ['__EMPTY_JSON_OBJECT__' => true],
        [],
        'value',
        123,
    ]);
});

it('decodes complex nested structure', function (): void {
    $json = '{"users":[{"name":"John","meta":{}},{"name":"Jane","tags":[]}]}';
    $result = JsonDecoder::decode($json);
    expect($result)->toBe([
        'users' => [
            ['name' => 'John', 'meta' => ['__EMPTY_JSON_OBJECT__' => true]],
            ['name' => 'Jane', 'tags' => []],
        ],
    ]);
});

// ============================================================================
// decode() Method Tests - Special Characters and Unicode
// ============================================================================

it('decodes string with unicode characters', function (): void {
    $result = JsonDecoder::decode('{"text":"Hello \u4e16\u754c"}');
    expect($result)->toBe(['text' => 'Hello 世界']);
});

it('decodes string with escaped characters', function (): void {
    $result = JsonDecoder::decode('{"quote":"He said \"hello\""}');
    expect($result)->toBe(['quote' => 'He said "hello"']);
});

it('decodes string with newlines', function (): void {
    $result = JsonDecoder::decode('{"text":"line1\\nline2"}');
    expect($result)->toBe(['text' => "line1\nline2"]);
});

it('decodes string with backslashes', function (): void {
    $result = JsonDecoder::decode('{"path":"C:\\\\Users\\\\test"}');
    expect($result)->toBe(['path' => 'C:\\Users\\test']);
});

it('decodes emoji characters', function (): void {
    $result = JsonDecoder::decode('{"emoji":"😀🎉"}');
    expect($result)->toBe(['emoji' => '😀🎉']);
});

// ============================================================================
// decode() Method Tests - Edge Cases
// ============================================================================

it('decodes large numbers', function (): void {
    $result = JsonDecoder::decode('9007199254740991');
    expect($result)->toBe(9_007_199_254_740_991);
});

it('decodes negative numbers', function (): void {
    $result = JsonDecoder::decode('-42');
    expect($result)->toBe(-42);
});

it('decodes scientific notation', function (): void {
    $result = JsonDecoder::decode('1.23e10');
    expect($result)->toBe(1.23e10);
});

it('decodes empty string', function (): void {
    $result = JsonDecoder::decode('""');
    expect($result)->toBe('');
});

it('decodes object with numeric keys', function (): void {
    $result = JsonDecoder::decode('{"0":"zero","1":"one"}');
    expect($result)->toBe(['0' => 'zero', '1' => 'one']);
});

it('decodes deeply nested arrays', function (): void {
    $result = JsonDecoder::decode('[[[[[1]]]]]');
    expect($result)->toBe([[[[[1]]]]]);
});

it('decodes object with special property names', function (): void {
    $result = JsonDecoder::decode('{"@type":"Person","$ref":"#/definitions/user"}');
    expect($result)->toBe(['@type' => 'Person', '$ref' => '#/definitions/user']);
});

// ============================================================================
// isEmptyObject() Method Tests - Happy Path
// ============================================================================

it('returns true for empty object marker', function (): void {
    $value = ['__EMPTY_JSON_OBJECT__' => true];
    expect(JsonDecoder::isEmptyObject($value))->toBeTrue();
});

it('returns false for empty array', function (): void {
    $value = [];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for non-empty array', function (): void {
    $value = ['key' => 'value'];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for string', function (): void {
    $value = 'string';
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for number', function (): void {
    $value = 42;
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for boolean', function (): void {
    $value = true;
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for null', function (): void {
    $value = null;
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for object', function (): void {
    $value = new stdClass();
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

// ============================================================================
// isEmptyObject() Method Tests - Edge Cases
// ============================================================================

it('returns false for marker with wrong value', function (): void {
    $value = ['__EMPTY_JSON_OBJECT__' => false];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns true for marker even with additional keys', function (): void {
    // The implementation only checks if marker exists and is true, not if it's the only key
    $value = ['__EMPTY_JSON_OBJECT__' => true, 'other' => 'data'];
    expect(JsonDecoder::isEmptyObject($value))->toBeTrue();
});

it('returns false for array with only marker key but wrong value type', function (): void {
    $value = ['__EMPTY_JSON_OBJECT__' => 'true'];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for array with marker-like key in different case', function (): void {
    $value = ['__empty_json_object__' => true];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

it('returns false for numeric array', function (): void {
    $value = [1, 2, 3];
    expect(JsonDecoder::isEmptyObject($value))->toBeFalse();
});

// ============================================================================
// Integration Tests - Real-world JSON Schema Scenarios
// ============================================================================

it('preserves empty object in schema properties', function (): void {
    $json = '{"properties":{"meta":{"type":"object","default":{}}}}';
    $result = JsonDecoder::decode($json);
    expect($result['properties']['meta']['default'])->toBe(['__EMPTY_JSON_OBJECT__' => true])
        ->and(JsonDecoder::isEmptyObject($result['properties']['meta']['default']))->toBeTrue();
});

it('preserves empty array in schema items', function (): void {
    $json = '{"items":{"type":"array","default":[]}}';
    $result = JsonDecoder::decode($json);
    expect($result['items']['default'])->toBe([])
        ->and(JsonDecoder::isEmptyObject($result['items']['default']))->toBeFalse();
});

it('handles JSON Schema with mixed empty values', function (): void {
    $json = '{
        "type": "object",
        "properties": {
            "emptyObj": {"default": {}},
            "emptyArr": {"default": []},
            "nested": {
                "properties": {
                    "innerEmpty": {"default": {}}
                }
            }
        }
    }';
    $result = JsonDecoder::decode($json);

    expect(JsonDecoder::isEmptyObject($result['properties']['emptyObj']['default']))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($result['properties']['emptyArr']['default']))->toBeFalse()
        ->and(JsonDecoder::isEmptyObject($result['properties']['nested']['properties']['innerEmpty']['default']))->toBeTrue();
});

it('handles array of schemas with empty defaults', function (): void {
    $json = '[{"default":{}},{"default":[]}]';
    $result = JsonDecoder::decode($json);

    expect(JsonDecoder::isEmptyObject($result[0]['default']))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($result[1]['default']))->toBeFalse();
});

// ============================================================================
// Type Preservation Tests
// ============================================================================

it('preserves integer type', function (): void {
    $result = JsonDecoder::decode('{"count":42}');
    expect($result['count'])->toBeInt()
        ->and($result['count'])->toBe(42);
});

it('preserves float type', function (): void {
    $result = JsonDecoder::decode('{"price":19.99}');
    expect($result['price'])->toBeFloat()
        ->and($result['price'])->toBe(19.99);
});

it('preserves boolean type', function (): void {
    $result = JsonDecoder::decode('{"active":true,"deleted":false}');
    expect($result['active'])->toBeBool()
        ->and($result['active'])->toBeTrue()
        ->and($result['deleted'])->toBeFalse();
});

it('preserves null type', function (): void {
    $result = JsonDecoder::decode('{"value":null}');
    expect($result['value'])->toBeNull();
});

it('preserves string type for numbers in quotes', function (): void {
    $result = JsonDecoder::decode('{"id":"123"}');
    expect($result['id'])->toBeString()
        ->and($result['id'])->toBe('123');
});

// ============================================================================
// Round-trip Tests
// ============================================================================

it('can distinguish decoded empty object from empty array', function (): void {
    $emptyObj = JsonDecoder::decode('{}');
    $emptyArr = JsonDecoder::decode('[]');

    expect(JsonDecoder::isEmptyObject($emptyObj))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($emptyArr))->toBeFalse()
        ->and($emptyObj)->not->toBe($emptyArr);
});

it('maintains distinction in complex structures', function (): void {
    $json = '{"a":{},"b":[],"c":[{},[]],"d":{"e":{},"f":[]}}';
    $result = JsonDecoder::decode($json);

    expect(JsonDecoder::isEmptyObject($result['a']))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($result['b']))->toBeFalse()
        ->and(JsonDecoder::isEmptyObject($result['c'][0]))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($result['c'][1]))->toBeFalse()
        ->and(JsonDecoder::isEmptyObject($result['d']['e']))->toBeTrue()
        ->and(JsonDecoder::isEmptyObject($result['d']['f']))->toBeFalse();
});

// ============================================================================
// Error Handling and Invalid JSON
// ============================================================================

it('handles whitespace-only JSON', function (): void {
    $result = JsonDecoder::decode('   {}   ');
    expect(JsonDecoder::isEmptyObject($result))->toBeTrue();
});

it('handles JSON with whitespace in objects', function (): void {
    $result = JsonDecoder::decode('{ "key" : "value" }');
    expect($result)->toBe(['key' => 'value']);
});

it('handles multiline JSON', function (): void {
    $json = '{
        "name": "test",
        "value": 123
    }';
    $result = JsonDecoder::decode($json);
    expect($result)->toBe(['name' => 'test', 'value' => 123]);
});
