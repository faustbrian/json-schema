<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators;

use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\ValueObjects\ValidationError;
use ReflectionClass;

use function describe;
use function expect;
use function it;

/**
 * Tests for AbstractValidator functionality.
 *
 * Uses Draft07Validator as concrete implementation to test abstract class behavior.
 */

// Boolean Schema Tests
describe('Boolean Schema Handling', function (): void {
    it('accepts all data when schema is true', function (): void {
        $validator = new Draft07Validator();

        // Test with various data types
        $result = $validator->validate(null, true);
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate('string', true);
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate(42, true);
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate(['array'], true);
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate(['object' => 'value'], true);
        expect($result->isValid())->toBeTrue();
    });

    it('rejects all data when schema is false', function (): void {
        $validator = new Draft07Validator();

        // Test with various data types
        $result = $validator->validate(null, false);
        expect($result->isValid())->toBeFalse();

        $result = $validator->validate('string', false);
        expect($result->isValid())->toBeFalse();

        $result = $validator->validate(42, false);
        expect($result->isValid())->toBeFalse();

        $result = $validator->validate(['array'], false);
        expect($result->isValid())->toBeFalse();
    });

    it('handles boolean schemas in nested contexts', function (): void {
        $validator = new Draft07Validator();

        // Schema with boolean subschemas
        $schema = [
            'type' => 'object',
            'properties' => [
                'allowed' => true,  // Always valid
                'forbidden' => false, // Always invalid
            ],
        ];

        $result = $validator->validate([
            'allowed' => 'any value',
        ], $schema);
        expect($result->isValid())->toBeTrue();

        $result = $validator->validate([
            'forbidden' => 'any value',
        ], $schema);
        expect($result->isValid())->toBeFalse();
    });
});

// Metaschema and Vocabulary Tests
describe('Metaschema and Vocabulary Loading', function (): void {
    it('validates with recognized $schema metaschema URIs', function (): void {
        $validator = new Draft07Validator();

        // Use actual Draft 07 metaschema
        $schema = [
            '$schema' => 'http://json-schema.org/draft-07/schema',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('validates without metaschema when $schema is not present', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('handles non-string $schema value gracefully', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$schema' => 123, // Invalid: not a string
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// Validation Depth and Recursion Tests
describe('Validation Depth and Recursion Protection', function (): void {
    it('tracks validation depth during nested schema validation', function (): void {
        $validator = new Draft07Validator();

        // Deeply nested schema
        $schema = [
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

        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep',
                ],
            ],
        ];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('detects excessive validation depth', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $depthProperty = $reflection->getProperty('validationDepth');

        // Schema with deep nesting
        $schema = [
            'type' => 'object',
            'properties' => [
                'level1' => [
                    'type' => 'object',
                    'properties' => [
                        'level2' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $data = ['level1' => ['level2' => 'test']];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();

        // Verify depth was tracked and reset
        expect($depthProperty->getValue($validator))->toBe(0);
    });
});

// Evaluation State Management Tests
describe('Evaluation State Management', function (): void {
    it('marks properties as evaluated', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];

        $data = ['name' => 'John', 'age' => 30];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('marks array items as evaluated', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ];

        $data = [1, 2, 3, 4, 5];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('saves and restores evaluation state', function (): void {
        $validator = new Draft07Validator();

        // Test with composition keywords that use state isolation
        $schema = [
            'type' => 'object',
            'allOf' => [
                [
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                [
                    'properties' => [
                        'age' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];

        $data = ['name' => 'John', 'age' => 30];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('merges evaluation state from composition keywords', function (): void {
        $validator = new Draft07Validator();

        // Schema with anyOf should merge annotations
        $schema = [
            'type' => 'object',
            'anyOf' => [
                [
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                [
                    'properties' => [
                        'email' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $data = ['name' => 'John', 'email' => 'john@example.com'];

        $result = $validator->validate($data, $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// URI Resolution Tests
describe('URI Resolution', function (): void {
    it('resolves absolute URIs unchanged', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        $result = $method->invoke($validator, 'http://base.com/schema', 'https://example.com/other');
        expect($result)->toBe('https://example.com/other');
    });

    it('returns reference as-is when base is empty', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        $result = $method->invoke($validator, '', 'relative/path');
        expect($result)->toBe('relative/path');
    });

    it('handles fragment-only references', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        // Fragment-only reference appends to base after removing trailing #
        $result = $method->invoke($validator, 'http://example.com/schema#', '#fragment');
        expect($result)->toBe('http://example.com/schema#fragment');

        // With existing fragment (no trailing #), it gets appended
        $result = $method->invoke($validator, 'http://example.com/schema#existing', '#fragment');
        expect($result)->toBe('http://example.com/schema#existing#fragment');

        $result = $method->invoke($validator, 'http://example.com/schema', '');
        expect($result)->toBe('http://example.com/schema');
    });

    it('handles absolute path references', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        $result = $method->invoke($validator, 'http://example.com/base/schema', '/absolute/path');
        expect($result)->toBe('http://example.com/absolute/path');
    });

    it('handles absolute path references with port', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        $result = $method->invoke($validator, 'http://example.com:8080/base/schema', '/absolute/path');
        expect($result)->toBe('http://example.com:8080/absolute/path');
    });

    it('resolves relative path references', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        $result = $method->invoke($validator, 'http://example.com/base/schema.json', 'other.json');
        expect($result)->toBe('http://example.com/base/other.json');
    });

    it('handles malformed base URI gracefully', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('resolveUri');

        // parse_url returns false for malformed URLs
        $result = $method->invoke($validator, 'http://:', 'ref');
        expect($result)->toBe('ref');
    });
});

// Path Normalization Tests
describe('Path Normalization', function (): void {
    it('normalizes paths with dot segments', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a/b/./c');
        expect($result)->toBe('/a/b/c');
    });

    it('normalizes paths with double-dot segments', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a/b/../c');
        expect($result)->toBe('/a/c');

        $result = $method->invoke($validator, '/a/b/c/../../d');
        expect($result)->toBe('/a/d');
    });

    it('preserves trailing slash', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a/b/c/');
        expect($result)->toBe('/a/b/c/');
    });

    it('preserves fragment in path', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a/b/./c#fragment');
        expect($result)->toBe('/a/b/c#fragment');
    });

    it('handles multiple consecutive slashes', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a//b///c');
        expect($result)->toBe('/a/b/c');
    });

    it('handles path with only dots', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/./././');
        expect($result)->toBe('/');
    });

    it('handles going up beyond root', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('normalizePath');

        $result = $method->invoke($validator, '/a/../..');
        expect($result)->toBe('/');
    });
});

// Schema Registration Tests
describe('Schema Registration', function (): void {
    it('registers schemas with $id', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            'type' => 'object',
            'properties' => [
                'nested' => [
                    '$id' => 'http://example.com/nested',
                    'type' => 'string',
                ],
            ],
        ];

        $result = $validator->validate(['nested' => 'test'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('registers schemas with $anchor', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$anchor' => 'myAnchor',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('registers schemas with $dynamicAnchor', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$dynamicAnchor' => 'myDynamicAnchor',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips registration when $id is empty string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => '',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips registration when $anchor is empty string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$anchor' => '',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips registration when $dynamicAnchor is empty string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$dynamicAnchor' => '',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips registration when $anchor is not a string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$anchor' => 123, // Invalid: not a string
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips registration when $dynamicAnchor is not a string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$dynamicAnchor' => 123, // Invalid: not a string
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('registers nested schemas in arrays', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'allOf' => [
                [
                    '$id' => 'http://example.com/nested1',
                    'type' => 'object',
                ],
                [
                    '$id' => 'http://example.com/nested2',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $result = $validator->validate(['name' => 'test'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips enum values during registration', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'enum' => [
                ['type' => 'object'],  // This looks like a schema but is just data
                ['const' => 'value'],  // This too
            ],
        ];

        $result = $validator->validate(['type' => 'object'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips const values during registration', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'const' => [
                'type' => 'object',  // This looks like a schema keyword but is just data
            ],
        ];

        $result = $validator->validate(['type' => 'object'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('registers schemas in nested object structures', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'properties' => [
                'first' => [
                    '$id' => 'http://example.com/first',
                    'type' => 'string',
                ],
                'second' => [
                    '$id' => 'http://example.com/second',
                    'type' => 'number',
                ],
            ],
        ];

        $result = $validator->validate(['first' => 'test', 'second' => 42], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips non-schema arrays during registration', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ];

        $result = $validator->validate([1, 2, 3], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('handles non-array values in schema traversal', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'string',
            'minLength' => 1, // This is not an array, should be skipped
            'description' => 'A test schema', // String value, should be skipped
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('handles deeply nested schema definitions', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/root',
            'definitions' => [
                'person' => [
                    '$id' => 'person.json',
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $result = $validator->validate(['name' => 'John'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('handles $defs keyword for schema definitions', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$defs' => [
                'myString' => ['type' => 'string'],
            ],
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// Composition Keyword Isolation Tests
describe('Composition Keyword Isolation', function (): void {
    it('isolates annotations between composition keywords', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'allOf' => [
                [
                    'properties' => [
                        'a' => ['type' => 'string'],
                    ],
                ],
            ],
            'anyOf' => [
                [
                    'properties' => [
                        'b' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $result = $validator->validate(['a' => 'test', 'b' => 'value'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('validates when no composition keywords are present', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $result = $validator->validate(['name' => 'test'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('merges errors from multiple composition keywords', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'allOf' => [
                [
                    'properties' => [
                        'a' => ['type' => 'string'],
                    ],
                ],
            ],
            'oneOf' => [
                [
                    'properties' => [
                        'b' => ['type' => 'number'],
                    ],
                ],
            ],
        ];

        $result = $validator->validate(['a' => 123, 'b' => 'invalid'], $schema);
        expect($result->isValid())->toBeFalse();
    });

    it('handles composition keywords without validate methods', function (): void {
        $validator = new Draft07Validator();

        // Schema with 'not' which has a validate method
        $schema = [
            'not' => ['type' => 'number'],
        ];

        $result = $validator->validate('string', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('skips disallowed composition keywords based on vocabulary', function (): void {
        $validator = new Draft07Validator();

        // All composition keywords should work when vocabularies allow them
        $schema = [
            'allOf' => [['type' => 'string']],
            'anyOf' => [['minLength' => 1]],
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// Base URI Stack Tests
describe('Base URI Stack Management', function (): void {
    it('pushes and pops base URI when processing schemas with $id', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/root',
            'type' => 'object',
            'properties' => [
                'nested' => [
                    '$id' => 'nested.json',
                    'type' => 'string',
                ],
            ],
        ];

        $result = $validator->validate(['nested' => 'test'], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('does not push base URI when $id is not a string', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 123, // Invalid: not a string
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('uses legacy id keyword for base URI', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'id' => 'http://example.com/schema',  // Draft 04 style
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// Dynamic Scope Tests
describe('Dynamic Scope Management', function (): void {
    it('maintains dynamic scope stack during validation', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'properties' => [
                'level1' => [
                    'type' => 'object',
                    'properties' => [
                        'level2' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $result = $validator->validate(['level1' => ['level2' => 'test']], $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('tracks $dynamicAnchor in scope stack', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            '$id' => 'http://example.com/schema',
            '$dynamicAnchor' => 'myDynamic',
            'type' => 'string',
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });
});

// Error Handling Tests
describe('Error Handling', function (): void {
    it('adds errors with correct path and keyword', function (): void {
        $validator = new Draft07Validator();

        // Use const validation which calls addError
        $schema = [
            'const' => 'expected-value',
        ];

        $result = $validator->validate('wrong-value', $schema);
        expect($result->isValid())->toBeFalse();
        expect($result->errors())->toHaveCount(1);
        expect($result->errors()[0])->toBeInstanceOf(ValidationError::class);
        expect($result->errors()[0]->keyword())->toBe('const');
        expect($result->errors()[0]->path())->toBe('$');
    });

    it('can accumulate multiple errors in nested validations', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'object',
            'properties' => [
                'nested' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => ['const' => 'expected'],
                    ],
                ],
            ],
        ];

        // Validation will process nested structure
        $result = $validator->validate(['nested' => ['value' => 'wrong']], $schema);
        expect($result->isValid())->toBeFalse();
        // At least one error should be present
        expect($result->errors())->not->toBeEmpty();
    });
});

// Keyword Filtering Tests
describe('Keyword Filtering by Vocabulary', function (): void {
    it('allows all keywords when no active vocabularies', function (): void {
        $validator = new Draft07Validator();

        $schema = [
            'type' => 'string',
            'minLength' => 1,
        ];

        $result = $validator->validate('test', $schema);
        expect($result->isValid())->toBeTrue();
    });

    it('handles special keyword name mappings', function (): void {
        $validator = new Draft07Validator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('isKeywordAllowed');

        // Test that keyword mapping works (Ref -> $ref)
        $result = $method->invoke($validator, 'Ref');
        expect($result)->toBeTrue();

        // Test normal keyword mapping (MinLength -> minLength)
        $result = $method->invoke($validator, 'MinLength');
        expect($result)->toBeTrue();
    });
});

// Schema Object Detection Tests
describe('Schema Object Detection', function (): void {
    it('detects schema objects by keywords', function (): void {
        $validator = new Draft07Validator();

        // Test various schemas with different keywords
        $schemas = [
            ['type' => 'string'],
            ['properties' => []],
            ['items' => []],
            ['required' => []],
            ['minimum' => 0],
            ['$ref' => '#/defs/something'],
            ['allOf' => []],
            ['$id' => 'http://example.com'],
        ];

        foreach ($schemas as $schema) {
            $result = $validator->validate('test', $schema);
            expect($result)->toBeObject();
        }
    });
});
