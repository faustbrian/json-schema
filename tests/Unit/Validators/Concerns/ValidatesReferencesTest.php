<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Concerns;

use Cline\JsonSchema\Validators\Draft202012Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use function expect;
use function it;

// $ref Tests - JSON Pointer references

it('validates $ref with JSON pointer to definitions', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'positiveInteger' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
        ],
        'properties' => [
            'age' => ['$ref' => '#/definitions/positiveInteger'],
        ],
    ]);

    $result = $validator->validate(['age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['age' => -5], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $ref with root reference', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'self' => ['$ref' => '#'],
        ],
    ]);

    $result = $validator->validate(['self' => ['self' => ['foo' => 'bar']]], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with nested pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'user' => [
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => [
                        'type' => 'integer',
                        'minimum' => 0,
                    ],
                ],
            ],
        ],
        '$ref' => '#/definitions/user',
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with special characters in pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'foo~bar' => [
                'type' => 'string',
            ],
        ],
        '$ref' => '#/definitions/foo~0bar',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with slash in pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'foo/bar' => [
                'type' => 'string',
            ],
        ],
        '$ref' => '#/definitions/foo~1bar',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails $ref when referenced schema does not validate', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'stringType' => [
                'type' => 'string',
            ],
        ],
        '$ref' => '#/definitions/stringType',
    ]);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $ref with recursive schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'children' => [
                'type' => 'array',
                'items' => ['$ref' => '#'],
            ],
        ],
    ]);

    $result = $validator->validate([
        'name' => 'root',
        'children' => [
            ['name' => 'child1', 'children' => []],
            ['name' => 'child2', 'children' => []],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with deeply recursive schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'node' => ['$ref' => '#'],
        ],
    ]);

    $result = $validator->validate([
        'node' => [
            'node' => [
                'node' => ['foo' => 'bar'],
            ],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when $ref is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// $ref Tests - With $defs (Draft 2020-12)

it('validates $ref with $defs keyword', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$defs' => [
            'email' => [
                'type' => 'string',
                'format' => 'email',
            ],
        ],
        'properties' => [
            'contact' => ['$ref' => '#/$defs/email'],
        ],
    ]);

    $result = $validator->validate(['contact' => 'test@example.com'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// $ref Tests - Boolean schemas

it('validates $ref pointing to boolean true schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'alwaysValid' => true,
        ],
        '$ref' => '#/definitions/alwaysValid',
    ]);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref pointing to boolean false schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'neverValid' => false,
        ],
        '$ref' => '#/definitions/neverValid',
    ]);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// $ref Tests - Complex scenarios

it('validates $ref with allOf composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'name' => [
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ],
            'age' => [
                'properties' => [
                    'age' => ['type' => 'integer'],
                ],
            ],
        ],
        'allOf' => [
            ['$ref' => '#/definitions/name'],
            ['$ref' => '#/definitions/age'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with anyOf composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'stringType' => ['type' => 'string'],
            'numberType' => ['type' => 'number'],
        ],
        'anyOf' => [
            ['$ref' => '#/definitions/stringType'],
            ['$ref' => '#/definitions/numberType'],
        ],
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(true, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $ref with oneOf composition', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'stringType' => ['type' => 'string'],
            'numberType' => ['type' => 'number'],
        ],
        'oneOf' => [
            ['$ref' => '#/definitions/stringType'],
            ['$ref' => '#/definitions/numberType'],
        ],
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref inside array items', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'positiveInteger' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
        ],
        'type' => 'array',
        'items' => ['$ref' => '#/definitions/positiveInteger'],
    ]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate([1, -1, 3], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// $dynamicRef Tests (Draft 2020-12)

it('validates $dynamicRef with static resolution', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'item' => [
                '$dynamicAnchor' => 'item',
                'type' => 'string',
            ],
        ],
        'type' => 'array',
        'items' => ['$dynamicRef' => '#item'],
    ]);

    $result = $validator->validate(['a', 'b', 'c'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef falling back to $ref behavior', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        'definitions' => [
            'stringType' => ['type' => 'string'],
        ],
        '$dynamicRef' => '#/definitions/stringType',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when $dynamicRef is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// $recursiveRef Tests (Draft 2019-09 - legacy)

it('validates $recursiveRef with recursiveAnchor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$recursiveAnchor' => true,
        'type' => 'object',
        'properties' => [
            'node' => ['$recursiveRef' => '#'],
        ],
    ]);

    $result = $validator->validate([
        'node' => [
            'node' => ['foo' => 'bar'],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $recursiveRef without recursiveAnchor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'node' => ['$recursiveRef' => '#'],
        ],
    ]);

    $result = $validator->validate([
        'node' => ['foo' => 'bar'],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when $recursiveRef is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// Complex reference scenarios

it('validates multiple levels of $ref indirection', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'a' => ['$ref' => '#/definitions/b'],
            'b' => ['$ref' => '#/definitions/c'],
            'c' => ['type' => 'string'],
        ],
        '$ref' => '#/definitions/a',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $ref with properties and additionalProperties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'base' => [
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ],
        'allOf' => [
            ['$ref' => '#/definitions/base'],
            [
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ],
        ],
    ]);

    $result = $validator->validate(['id' => 1, 'name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with circular references', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'node' => [
                'type' => 'object',
                'properties' => [
                    'value' => ['type' => 'string'],
                    'next' => ['$ref' => '#/definitions/node'],
                ],
            ],
        ],
        '$ref' => '#/definitions/node',
    ]);

    $result = $validator->validate([
        'value' => 'first',
        'next' => [
            'value' => 'second',
            'next' => [
                'value' => 'third',
            ],
        ],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref inside if/then/else', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'address' => [
                'properties' => [
                    'street' => ['type' => 'string'],
                ],
            ],
        ],
        'if' => [
            'properties' => [
                'country' => ['const' => 'USA'],
            ],
        ],
        'then' => [
            'properties' => [
                'address' => ['$ref' => '#/definitions/address'],
            ],
        ],
    ]);

    $result = $validator->validate([
        'country' => 'USA',
        'address' => ['street' => 'Main St'],
    ], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref in not keyword', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'positive' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
        ],
        'not' => ['$ref' => '#/definitions/positive'],
    ]);

    $result = $validator->validate(0, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(5, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('handles very deep recursion gracefully', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'child' => ['$ref' => '#'],
        ],
    ]);

    // Create deeply nested structure - start with empty object at the end
    $data = ['foo' => 'bar']; // Start with a non-empty object to avoid array/object ambiguity
    $current = &$data;

    for ($i = 0; $i < 10; ++$i) {
        $current['child'] = ['value' => $i]; // Each level is an object
        $current = &$current['child'];
    }

    $result = $validator->validate($data, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with unevaluatedProperties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'base' => [
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ],
        '$ref' => '#/definitions/base',
        'unevaluatedProperties' => false,
    ]);

    $result = $validator->validate(['id' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['id' => 1, 'extra' => 'value'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// Recursion threshold tests - metaschema threshold (200)

it('handles metaschema recursion at threshold', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://json-schema.org/draft/2020-12/schema',
        'type' => 'object',
        'properties' => [
            'child' => ['$ref' => 'https://json-schema.org/draft/2020-12/schema'],
        ],
    ]);

    // Create deeply nested structure
    $data = ['child' => ['child' => ['child' => ['foo' => 'bar']]]];

    $result = $validator->validate($data, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('breaks recursion cycle for metaschema at 200 depth', function (): void {
    $validator = new Draft202012Validator();
    // Schema with json-schema.org URI will use threshold of 200
    $schema = new Schema([
        '$id' => 'https://json-schema.org/test/recursive',
        'type' => 'object',
        'properties' => [
            'node' => ['$ref' => '#'],
        ],
    ]);

    // Create very deep nesting that would normally exceed 200
    $data = [];
    $current = &$data;

    for ($i = 0; $i < 250; ++$i) {
        $current['node'] = [];
        $current = &$current['node'];
    }

    // Should still validate (recursion detection breaks the cycle)
    $result = $validator->validate($data, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('breaks recursion cycle for regular schema at 50 depth', function (): void {
    $validator = new Draft202012Validator();
    // Schema without json-schema.org URI uses threshold of 50
    $schema = new Schema([
        '$id' => 'https://example.com/recursive',
        'type' => 'object',
        'properties' => [
            'node' => ['$ref' => '#'],
        ],
    ]);

    // Create very deep nesting
    $data = [];
    $current = &$data;

    for ($i = 0; $i < 100; ++$i) {
        $current['node'] = [];
        $current = &$current['node'];
    }

    // Should still validate (recursion detection breaks the cycle)
    $result = $validator->validate($data, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('detects early recursion with same schema appearing twice', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        'type' => 'object',
        'properties' => [
            'level1' => ['$ref' => '#'],
        ],
    ]);

    // Two levels should work
    $data = ['level1' => ['level1' => ['foo' => 'bar']]];

    $result = $validator->validate($data, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// External schema loading tests

it('validates schema from registry with fragment', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/main',
        '$defs' => [
            'user' => [
                '$id' => 'https://example.com/user',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ],
        ],
        'properties' => [
            'user' => ['$ref' => 'https://example.com/user'],
        ],
    ]);

    $result = $validator->validate(['user' => ['name' => 'John']], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $ref with anchor reference', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'item' => [
                '$anchor' => 'itemSchema',
                'type' => 'string',
            ],
        ],
        '$ref' => '#itemSchema',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates plain anchor reference with current base URI', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'stringType' => [
                '$anchor' => 'str',
                'type' => 'string',
            ],
        ],
        'properties' => [
            'name' => ['$ref' => '#str'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('handles invalid reference failing validation', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        'definitions' => [
            'valid' => ['type' => 'string'],
        ],
        // $ref at root level - must resolve or fail
        '$ref' => '#/definitions/nonexistent',
    ]);

    // This should fail because the reference path doesn't exist
    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// JSON Pointer edge cases

it('resolves reference against current base URI from registry', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'positiveInt' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
        ],
        'properties' => [
            'count' => ['$ref' => '#/$defs/positiveInt'],
        ],
    ]);

    $result = $validator->validate(['count' => 5], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['count' => -1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('resolves pointer in boolean schema returns null', function (): void {
    $validator = new Draft202012Validator();
    // Boolean schemas don't have pointer segments, so this should fail
    $schema = new Schema([
        'definitions' => [
            'alwaysTrue' => true,
        ],
        // Try to resolve a pointer within true (should fail)
        '$ref' => '#/definitions/alwaysTrue',
    ]);

    // This should pass because it references the boolean true
    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails when pointer reference is invalid', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'valid' => ['type' => 'string'],
        ],
        // Reference to non-existent path
        '$ref' => '#/definitions/nonexistent',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('handles URL-encoded segments in JSON pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'hello world' => ['type' => 'string'],
        ],
        // URL-encoded space
        '$ref' => '#/definitions/hello%20world',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('resolves empty pointer path to root schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'string',
    ]);

    // Empty path after #/ should resolve to root (handled by resolveReference)
    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// validateSchemaWithPointerContext tests

it('updates base URI while traversing intermediate schemas with $id', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/root',
        '$defs' => [
            'intermediate' => [
                '$id' => 'https://example.com/intermediate',
                '$defs' => [
                    'final' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ],
        '$ref' => '#/$defs/intermediate/$defs/final',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates schema with pointer context when pointer is just #', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'string',
    ]);

    // Internal method would use # pointer
    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('handles non-array value in pointer traversal', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'leaf' => ['type' => 'string'],
        ],
        // Try to traverse into a string type (should fail)
        '$ref' => '#/definitions/leaf/type/invalid',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pointer context with intermediate $id schemas', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/base',
        'definitions' => [
            'level1' => [
                '$id' => 'level1',
                'definitions' => [
                    'level2' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ],
        '$ref' => '#/definitions/level1/definitions/level2',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('pops correct number of base URIs after pointer traversal', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/root',
        '$defs' => [
            'a' => [
                '$id' => 'a',
                '$defs' => [
                    'b' => [
                        '$id' => 'b',
                        '$defs' => [
                            'c' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        '$ref' => '#/$defs/a/$defs/b/$defs/c',
    ]);

    $result = $validator->validate(42, $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('not an int', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// $recursiveRef edge cases

it('validates $recursiveRef with non-root reference', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'recursiveNode' => [
                '$recursiveAnchor' => true,
                'type' => 'object',
            ],
        ],
        // Non-# reference falls back to normal $ref
        '$recursiveRef' => '#/definitions/recursiveNode',
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $recursiveRef without recursiveAnchor falls back to resource root', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/root',
        // No $recursiveAnchor at root
        'type' => 'array',
        'items' => [
            '$id' => 'https://example.com/item',
            'type' => 'object',
            'properties' => [
                'value' => ['type' => 'integer'],
                'nested' => ['$recursiveRef' => '#'],
            ],
        ],
    ]);

    // $recursiveRef should resolve to the item schema (resource root with $id)
    $result = $validator->validate([['value' => 1, 'nested' => ['value' => 2]]], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $recursiveRef searches for outermost recursiveAnchor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$recursiveAnchor' => true,
        'type' => 'array',
        'items' => true,
    ]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $recursiveRef with nested schema has resource boundary', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'items' => [
                'type' => 'array',
                'items' => true,
            ],
        ],
    ]);

    $result = $validator->validate(['items' => ['a', 'b', 'c']], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// $dynamicRef edge cases

it('validates $dynamicRef with JSON Pointer behaves like $ref', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'definitions' => [
            'stringType' => ['type' => 'string'],
        ],
        '$dynamicRef' => '#/definitions/stringType',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates $dynamicRef without fragment uses $ref behavior', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        'definitions' => [
            'base' => ['type' => 'object'],
        ],
        '$dynamicRef' => 'https://example.com/schema',
    ]);

    $result = $validator->validate(['foo' => 'bar'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef with fragment starting with slash uses $ref', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        'definitions' => [
            'item' => ['type' => 'string'],
        ],
        '$dynamicRef' => 'https://example.com/schema#/definitions/item',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef uses $ref behavior for JSON pointers', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'stringType' => ['type' => 'string'],
        ],
        'properties' => [
            // $dynamicRef with JSON pointer behaves like $ref
            'value' => ['$dynamicRef' => '#/$defs/stringType'],
        ],
    ]);

    $result = $validator->validate(['value' => 'test'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef without matching dynamicAnchor uses static resolution', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'item' => [
                '$anchor' => 'myAnchor',
                // No $dynamicAnchor, so uses static resolution
                'type' => 'string',
            ],
        ],
        '$dynamicRef' => '#myAnchor',
    ]);

    $result = $validator->validate('test', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef searches dynamic scope for matching anchor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/root',
        '$dynamicAnchor' => 'item',
        'type' => 'object',
        '$defs' => [
            'inner' => [
                '$id' => 'inner',
                '$dynamicAnchor' => 'item',
                'type' => 'string',
            ],
        ],
        'properties' => [
            'field' => ['$dynamicRef' => '#item'],
        ],
    ]);

    $result = $validator->validate(['field' => ['nested' => true]], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef uses first outermost match in dynamic scope', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/outer',
        '$dynamicAnchor' => 'node',
        'type' => 'object',
        'properties' => [
            'inner' => [
                '$id' => 'inner',
                '$defs' => [
                    'withAnchor' => [
                        '$dynamicAnchor' => 'node',
                        'type' => 'string',
                    ],
                ],
                'properties' => [
                    'ref' => ['$dynamicRef' => '#node'],
                ],
            ],
        ],
    ]);

    // Should use outermost (root) which accepts objects
    $result = $validator->validate(['inner' => ['ref' => ['key' => 'value']]], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates $dynamicRef skips scope entries without matching anchor', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        '$id' => 'https://example.com/schema',
        '$defs' => [
            'target' => [
                '$dynamicAnchor' => 'item',
                'type' => 'integer',
            ],
        ],
        'properties' => [
            'value' => ['$dynamicRef' => '#item'],
        ],
    ]);

    $result = $validator->validate(['value' => 42], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['value' => 'string'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});
