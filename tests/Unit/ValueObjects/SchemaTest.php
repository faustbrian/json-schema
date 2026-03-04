<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\ValueObjects;

use Cline\JsonSchema\Exceptions\InvalidJsonSchemaException;
use Cline\JsonSchema\ValueObjects\Schema;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use function expect;
use function it;
use function json_decode;
use function str_repeat;

// ============================================================================
// Constructor Tests
// ============================================================================

it('creates schema with empty array', function (): void {
    $schema = new Schema([]);
    expect($schema->toArray())->toBe([]);
});

it('creates schema with simple data', function (): void {
    $data = ['type' => 'string'];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('creates schema with complex nested data', function (): void {
    $data = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer', 'minimum' => 0],
        ],
        'required' => ['name'],
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('creates schema with all common keywords', function (): void {
    $data = [
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'title' => 'User Schema',
        'description' => 'Schema for user objects',
        'type' => 'object',
        'properties' => ['id' => ['type' => 'integer']],
        'required' => ['id'],
        'additionalProperties' => false,
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

// ============================================================================
// fromJson() Method Tests - Happy Path
// ============================================================================

it('creates schema from valid JSON string', function (): void {
    $json = '{"type":"string"}';
    $schema = Schema::fromJson($json);
    expect($schema->toArray())->toBe(['type' => 'string']);
});

it('creates schema from JSON with whitespace', function (): void {
    $json = '  {  "type"  :  "string"  }  ';
    $schema = Schema::fromJson($json);
    expect($schema->toArray())->toBe(['type' => 'string']);
});

it('creates schema from pretty-printed JSON', function (): void {
    $json = <<<'JSON'
{
    "type": "object",
    "properties": {
        "name": {
            "type": "string"
        }
    }
}
JSON;
    $schema = Schema::fromJson($json);
    expect($schema->get('type'))->toBe('object')
        ->and($schema->get('properties'))->toBeArray()
        ->and($schema->get('properties')['name'])->toBe(['type' => 'string']);
});

it('creates schema from JSON with unicode characters', function (): void {
    $json = '{"description":"User with emoji 🚀 and special chars: é, ñ, ü"}';
    $schema = Schema::fromJson($json);
    expect($schema->get('description'))->toBe('User with emoji 🚀 and special chars: é, ñ, ü');
});

it('creates schema from JSON with escaped characters', function (): void {
    $json = '{"pattern":"^[a-z]+$","description":"Line 1\\nLine 2\\tTabbed"}';
    $schema = Schema::fromJson($json);
    expect($schema->get('pattern'))->toBe('^[a-z]+$')
        ->and($schema->get('description'))->toBe("Line 1\nLine 2\tTabbed");
});

it('creates schema from JSON with null values', function (): void {
    $json = '{"type":"string","default":null}';
    $schema = Schema::fromJson($json);
    expect($schema->get('default'))->toBeNull();
});

it('creates schema from JSON with boolean values', function (): void {
    $json = '{"required":true,"additionalProperties":false}';
    $schema = Schema::fromJson($json);
    expect($schema->get('required'))->toBeTrue()
        ->and($schema->get('additionalProperties'))->toBeFalse();
});

it('creates schema from JSON with numeric values', function (): void {
    $json = '{"minimum":0,"maximum":100,"multipleOf":5.5}';
    $schema = Schema::fromJson($json);
    expect($schema->get('minimum'))->toBe(0)
        ->and($schema->get('maximum'))->toBe(100)
        ->and($schema->get('multipleOf'))->toBe(5.5);
});

it('creates schema from JSON with arrays', function (): void {
    $json = '{"enum":["red","green","blue"],"required":["id","name"]}';
    $schema = Schema::fromJson($json);
    expect($schema->get('enum'))->toBe(['red', 'green', 'blue'])
        ->and($schema->get('required'))->toBe(['id', 'name']);
});

it('creates schema from empty JSON object', function (): void {
    $json = '{}';
    $schema = Schema::fromJson($json);
    expect($schema->toArray())->toBe([]);
});

// ============================================================================
// fromJson() Method Tests - Sad Path
// ============================================================================

it('throws exception for invalid JSON syntax', function (): void {
    expect(fn (): Schema => Schema::fromJson('{invalid json}'))
        ->toThrow(InvalidJsonSchemaException::class);
});

it('throws exception for JSON string value instead of object', function (): void {
    expect(fn (): Schema => Schema::fromJson('"string value"'))
        ->toThrow(InvalidJsonSchemaException::class, 'JSON must decode to an array');
});

it('throws exception for JSON number value instead of object', function (): void {
    expect(fn (): Schema => Schema::fromJson('123'))
        ->toThrow(InvalidJsonSchemaException::class, 'JSON must decode to an array');
});

it('throws exception for JSON boolean value instead of object', function (): void {
    expect(fn (): Schema => Schema::fromJson('true'))
        ->toThrow(InvalidJsonSchemaException::class, 'JSON must decode to an array');
});

it('throws exception for JSON null value instead of object', function (): void {
    expect(fn (): Schema => Schema::fromJson('null'))
        ->toThrow(InvalidJsonSchemaException::class, 'JSON must decode to an array');
});

it('creates schema from JSON array', function (): void {
    // JSON arrays are valid associative arrays in PHP
    $json = '[1, 2, 3]';
    $schema = Schema::fromJson($json);
    expect($schema->toArray())->toBe([0 => 1, 1 => 2, 2 => 3]);
});

it('throws exception for empty string', function (): void {
    expect(fn (): Schema => Schema::fromJson(''))
        ->toThrow(InvalidJsonSchemaException::class);
});

it('throws exception for malformed JSON with trailing comma', function (): void {
    expect(fn (): Schema => Schema::fromJson('{"type":"string",}'))
        ->toThrow(InvalidJsonSchemaException::class);
});

it('throws exception for malformed JSON with unquoted keys', function (): void {
    expect(fn (): Schema => Schema::fromJson('{type: "string"}'))
        ->toThrow(InvalidJsonSchemaException::class);
});

it('throws exception for malformed JSON with single quotes', function (): void {
    expect(fn (): Schema => Schema::fromJson("{'type':'string'}"))
        ->toThrow(InvalidJsonSchemaException::class);
});

// ============================================================================
// toArray() Method Tests
// ============================================================================

it('returns exact array passed to constructor', function (): void {
    $data = ['type' => 'string', 'minLength' => 5];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('returns immutable copy of array', function (): void {
    $data = ['type' => 'string'];
    $schema = new Schema($data);
    $array = $schema->toArray();
    $array['type'] = 'number';
    expect($schema->toArray())->toBe(['type' => 'string']);
});

it('returns array with nested structures', function (): void {
    $data = [
        'type' => 'object',
        'properties' => [
            'address' => [
                'type' => 'object',
                'properties' => [
                    'street' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                ],
            ],
        ],
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

// ============================================================================
// get() Method Tests
// ============================================================================

it('gets existing property value', function (): void {
    $schema = new Schema(['type' => 'string']);
    expect($schema->get('type'))->toBe('string');
});

it('gets null for non-existent property', function (): void {
    $schema = new Schema(['type' => 'string']);
    expect($schema->get('nonexistent'))->toBeNull();
});

it('gets null for property in empty schema', function (): void {
    $schema = new Schema([]);
    expect($schema->get('type'))->toBeNull();
});

it('gets nested property value', function (): void {
    $schema = new Schema(['properties' => ['name' => ['type' => 'string']]]);
    $properties = $schema->get('properties');
    expect($properties)->toBeArray()
        ->and($properties['name'])->toBe(['type' => 'string']);
});

it('gets array property value', function (): void {
    $schema = new Schema(['required' => ['id', 'name']]);
    expect($schema->get('required'))->toBe(['id', 'name']);
});

it('gets boolean property value', function (): void {
    $schema = new Schema(['additionalProperties' => false]);
    expect($schema->get('additionalProperties'))->toBeFalse();
});

it('gets numeric property value', function (): void {
    $schema = new Schema(['minimum' => 0, 'maximum' => 100]);
    expect($schema->get('minimum'))->toBe(0)
        ->and($schema->get('maximum'))->toBe(100);
});

it('gets null property value explicitly set to null', function (): void {
    $schema = new Schema(['default' => null]);
    expect($schema->get('default'))->toBeNull();
});

it('gets property with special characters in name', function (): void {
    $schema = new Schema(['$schema' => 'http://json-schema.org/draft-07/schema#']);
    expect($schema->get('$schema'))->toBe('http://json-schema.org/draft-07/schema#');
});

it('gets property with numeric string key', function (): void {
    $schema = new Schema(['123' => 'value']);
    expect($schema->get('123'))->toBe('value');
});

// ============================================================================
// has() Method Tests
// ============================================================================

it('returns true for existing property', function (): void {
    $schema = new Schema(['type' => 'string']);
    expect($schema->has('type'))->toBeTrue();
});

it('returns false for non-existent property', function (): void {
    $schema = new Schema(['type' => 'string']);
    expect($schema->has('nonexistent'))->toBeFalse();
});

it('returns false for property in empty schema', function (): void {
    $schema = new Schema([]);
    expect($schema->has('type'))->toBeFalse();
});

it('returns true for property with null value', function (): void {
    $schema = new Schema(['default' => null]);
    expect($schema->has('default'))->toBeTrue();
});

it('returns true for property with false value', function (): void {
    $schema = new Schema(['additionalProperties' => false]);
    expect($schema->has('additionalProperties'))->toBeTrue();
});

it('returns true for property with zero value', function (): void {
    $schema = new Schema(['minimum' => 0]);
    expect($schema->has('minimum'))->toBeTrue();
});

it('returns true for property with empty string value', function (): void {
    $schema = new Schema(['description' => '']);
    expect($schema->has('description'))->toBeTrue();
});

it('returns true for property with empty array value', function (): void {
    $schema = new Schema(['required' => []]);
    expect($schema->has('required'))->toBeTrue();
});

it('distinguishes between null value and missing property', function (): void {
    $schema = new Schema(['default' => null]);
    expect($schema->has('default'))->toBeTrue()
        ->and($schema->get('default'))->toBeNull()
        ->and($schema->has('missing'))->toBeFalse()
        ->and($schema->get('missing'))->toBeNull();
});

it('returns true for property with special characters', function (): void {
    $schema = new Schema(['$schema' => 'value']);
    expect($schema->has('$schema'))->toBeTrue();
});

// ============================================================================
// toJson() Method Tests - Happy Path
// ============================================================================

it('converts schema to JSON string', function (): void {
    $schema = new Schema(['type' => 'string']);
    $json = $schema->toJson();
    expect($json)->toBe('{"type":"string"}');
});

it('converts empty schema to JSON', function (): void {
    $schema = new Schema([]);
    expect($schema->toJson())->toBe('[]');
});

it('converts schema with nested objects to JSON', function (): void {
    $schema = new Schema([
        'type' => 'object',
        'properties' => ['name' => ['type' => 'string']],
    ]);
    $json = $schema->toJson();
    $decoded = json_decode($json, true);
    expect($decoded)->toBe([
        'type' => 'object',
        'properties' => ['name' => ['type' => 'string']],
    ]);
});

it('converts schema with arrays to JSON', function (): void {
    $schema = new Schema(['enum' => ['red', 'green', 'blue']]);
    $json = $schema->toJson();
    expect($json)->toBe('{"enum":["red","green","blue"]}');
});

it('converts schema with JSON_PRETTY_PRINT flag', function (): void {
    $schema = new Schema(['type' => 'string', 'minLength' => 5]);
    $json = $schema->toJson(JSON_PRETTY_PRINT);
    expect($json)->toContain("\n")
        ->and($json)->toContain('    ')
        ->and(json_decode($json, true))->toBe(['type' => 'string', 'minLength' => 5]);
});

it('converts schema with JSON_UNESCAPED_SLASHES flag', function (): void {
    $schema = new Schema(['$schema' => 'http://json-schema.org/draft-07/schema#']);
    $json = $schema->toJson(JSON_UNESCAPED_SLASHES);
    expect($json)->toContain('http://json-schema.org/draft-07/schema#')
        ->and($json)->not->toContain('\\/');
});

it('converts schema with multiple JSON flags', function (): void {
    $schema = new Schema(['type' => 'string']);
    $json = $schema->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    expect($json)->toContain("\n");
});

it('converts schema with unicode characters', function (): void {
    $schema = new Schema(['description' => 'Emoji 🚀 and special: é']);
    $json = $schema->toJson(JSON_UNESCAPED_UNICODE);
    expect($json)->toContain('🚀')
        ->and($json)->toContain('é');
});

it('converts schema with null values', function (): void {
    $schema = new Schema(['default' => null]);
    expect($schema->toJson())->toBe('{"default":null}');
});

it('converts schema with boolean values', function (): void {
    $schema = new Schema(['required' => true, 'additionalProperties' => false]);
    $json = $schema->toJson();
    expect($json)->toContain('"required":true')
        ->and($json)->toContain('"additionalProperties":false');
});

it('converts schema with numeric values', function (): void {
    $schema = new Schema(['minimum' => 0, 'maximum' => 100.5]);
    $json = $schema->toJson();
    expect($json)->toContain('"minimum":0')
        ->and($json)->toContain('"maximum":100.5');
});

// ============================================================================
// Round-trip Tests (JSON -> Schema -> JSON)
// ============================================================================

it('round-trips simple schema through JSON', function (): void {
    $original = '{"type":"string"}';
    $schema = Schema::fromJson($original);
    $result = $schema->toJson();
    expect($result)->toBe($original);
});

it('round-trips complex schema through JSON', function (): void {
    $data = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer', 'minimum' => 0],
        ],
        'required' => ['name'],
    ];
    $schema = new Schema($data);
    $json = $schema->toJson();
    $roundtrip = Schema::fromJson($json);
    expect($roundtrip->toArray())->toBe($data);
});

it('round-trips schema with special characters', function (): void {
    $data = ['description' => 'Special: é, ñ, ü, 🚀'];
    $schema = new Schema($data);
    $json = $schema->toJson(JSON_UNESCAPED_UNICODE);
    $roundtrip = Schema::fromJson($json);
    expect($roundtrip->toArray())->toBe($data);
});

// ============================================================================
// Edge Cases and Immutability Tests
// ============================================================================

it('maintains immutability after toArray call', function (): void {
    $schema = new Schema(['type' => 'string']);
    $array1 = $schema->toArray();
    $array2 = $schema->toArray();
    expect($array1)->toBe($array2);
    // Arrays are returned by value, so same content
});

it('handles schema with deeply nested structures', function (): void {
    $data = [
        'type' => 'object',
        'properties' => [
            'level1' => [
                'type' => 'object',
                'properties' => [
                    'level2' => [
                        'type' => 'object',
                        'properties' => [
                            'level3' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ],
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('handles schema with all primitive types', function (): void {
    $data = [
        'string' => 'value',
        'integer' => 42,
        'float' => 3.14,
        'boolean_true' => true,
        'boolean_false' => false,
        'null' => null,
        'array' => [1, 2, 3],
        'object' => ['nested' => 'value'],
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('handles schema with empty nested structures', function (): void {
    $data = [
        'properties' => [],
        'required' => [],
        'enum' => [],
    ];
    $schema = new Schema($data);
    expect($schema->toArray())->toBe($data);
});

it('handles very long property names', function (): void {
    $longKey = str_repeat('a', 1_000);
    $schema = new Schema([$longKey => 'value']);
    expect($schema->has($longKey))->toBeTrue()
        ->and($schema->get($longKey))->toBe('value');
});

it('handles large number of properties', function (): void {
    $data = [];

    for ($i = 0; $i < 100; ++$i) {
        $data['property'.$i] = ['type' => 'string'];
    }

    $schema = new Schema($data);
    expect($schema->toArray())->toHaveCount(100)
        ->and($schema->has('property0'))->toBeTrue()
        ->and($schema->has('property99'))->toBeTrue();
});

// ============================================================================
// Real-world Schema Examples
// ============================================================================

it('handles draft-07 meta-schema reference', function (): void {
    $data = [
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        '$id' => 'https://example.com/person.schema.json',
        'title' => 'Person',
        'type' => 'object',
    ];
    $schema = new Schema($data);
    expect($schema->get('$schema'))->toBe('http://json-schema.org/draft-07/schema#')
        ->and($schema->get('$id'))->toBe('https://example.com/person.schema.json');
});

it('handles schema with pattern properties', function (): void {
    $data = [
        'type' => 'object',
        'patternProperties' => [
            '^S_' => ['type' => 'string'],
            '^I_' => ['type' => 'integer'],
        ],
    ];
    $schema = new Schema($data);
    expect($schema->get('patternProperties'))->toBeArray()
        ->and($schema->get('patternProperties')['^S_'])->toBe(['type' => 'string']);
});

it('handles schema with definitions/defs', function (): void {
    $data = [
        'definitions' => [
            'address' => [
                'type' => 'object',
                'properties' => [
                    'street' => ['type' => 'string'],
                ],
            ],
        ],
    ];
    $schema = new Schema($data);
    expect($schema->has('definitions'))->toBeTrue()
        ->and($schema->get('definitions')['address']['type'])->toBe('object');
});

it('handles schema with conditional keywords', function (): void {
    $data = [
        'if' => ['properties' => ['country' => ['const' => 'US']]],
        'then' => ['properties' => ['zipCode' => ['pattern' => '^[0-9]{5}$']]],
        'else' => ['properties' => ['postalCode' => ['type' => 'string']]],
    ];
    $schema = new Schema($data);
    expect($schema->has('if'))->toBeTrue()
        ->and($schema->has('then'))->toBeTrue()
        ->and($schema->has('else'))->toBeTrue();
});

it('handles schema with composition keywords', function (): void {
    $data = [
        'allOf' => [
            ['type' => 'object'],
            ['properties' => ['name' => ['type' => 'string']]],
        ],
        'anyOf' => [
            ['required' => ['name']],
            ['required' => ['id']],
        ],
        'oneOf' => [
            ['properties' => ['type' => ['const' => 'A']]],
            ['properties' => ['type' => ['const' => 'B']]],
        ],
    ];
    $schema = new Schema($data);
    expect($schema->has('allOf'))->toBeTrue()
        ->and($schema->has('anyOf'))->toBeTrue()
        ->and($schema->has('oneOf'))->toBeTrue();
});

// ============================================================================
// toJson() Method Tests - Error Cases
// ============================================================================

it('throws exception when JSON encoding fails', function (): void {
    // Line 142: Test JSON encoding failure
    // Create a schema with data that cannot be encoded to JSON (e.g., resources, NAN)
    // In PHP, NAN actually can be encoded (becomes null), so we need something that truly fails
    // PHP resources or circular references would fail, but can't be put in array directly
    // Instead, we can test with invalid UTF-8 which causes json_encode to fail
    $invalidUtf8 = "\xB1\x31";
    $schema = new Schema(['description' => $invalidUtf8]);

    expect(fn (): string => $schema->toJson())
        ->toThrow(InvalidJsonSchemaException::class, 'Failed to encode schema to JSON');
});
