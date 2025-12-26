<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Factories\ValidatorFactory;
use Cline\JsonSchema\JsonSchemaManager;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function expect;
use function it;

// ============================================================================
// Constructor Tests
// ============================================================================

it('creates manager with validator factory', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    expect($manager)->toBeInstanceOf(JsonSchemaManager::class);
});

// ============================================================================
// validate() Method Tests - Happy Path with Auto-Detection
// ============================================================================

it('validates data against schema with auto-detected draft', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = ['name' => 'John', 'age' => 30];
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
        ],
    ];

    $result = $manager->validate($data, $schema);

    expect($result)->toBeInstanceOf(ValidationResult::class)
        ->and($result->isValid())->toBeTrue();
});

it('validates simple string type', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate('hello', ['type' => 'string']);

    expect($result->isValid())->toBeTrue();
});

it('validates simple integer type', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer']);

    expect($result->isValid())->toBeTrue();
});

it('validates simple boolean type', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(true, ['type' => 'boolean']);

    expect($result->isValid())->toBeTrue();
});

it('validates simple array type', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate([1, 2, 3], ['type' => 'array']);

    expect($result->isValid())->toBeTrue();
});

it('validates null type', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(null, ['type' => 'null']);

    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// validate() Method Tests - Draft Detection
// ============================================================================

it('detects Draft04 from schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('detects Draft06 from schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'http://json-schema.org/draft-06/schema#',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('detects Draft07 from schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('detects Draft201909 from schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('detects Draft202012 from schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('defaults to Draft202012 when no schema URI present', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = ['type' => 'string'];
    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('defaults to Draft202012 when schema URI is unrecognized', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'http://example.com/unknown-schema',
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

it('defaults to Draft202012 when schema URI is not a string', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 123, // Invalid type
        'type' => 'string',
    ];

    $result = $manager->validate('test', $schema);

    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// validate() Method Tests - Explicit Draft Parameter
// ============================================================================

it('uses explicit draft parameter when provided', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = ['type' => 'string'];
    $result = $manager->validate('test', $schema, Draft::Draft07);

    expect($result->isValid())->toBeTrue();
});

it('explicit draft overrides schema URI', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'type' => 'string',
    ];

    // Explicitly use Draft07 despite Draft04 in $schema
    $result = $manager->validate('test', $schema, Draft::Draft07);

    expect($result->isValid())->toBeTrue();
});

it('validates with explicit Draft04', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer'], Draft::Draft04);

    expect($result->isValid())->toBeTrue();
});

it('validates with explicit Draft06', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer'], Draft::Draft06);

    expect($result->isValid())->toBeTrue();
});

it('validates with explicit Draft07', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer'], Draft::Draft07);

    expect($result->isValid())->toBeTrue();
});

it('validates with explicit Draft201909', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer'], Draft::Draft201909);

    expect($result->isValid())->toBeTrue();
});

it('validates with explicit Draft202012', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(42, ['type' => 'integer'], Draft::Draft202012);

    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// validate() Method Tests - Validation Failures
// ============================================================================

it('returns invalid result for type mismatch', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate('not a number', ['type' => 'integer']);

    expect($result->isValid())->toBeFalse();
});

it('returns invalid result for missing required property', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'required' => ['name'],
    ];

    $result = $manager->validate(['other' => 'value'], $schema);

    expect($result->isValid())->toBeFalse();
});

it('returns invalid result for pattern mismatch', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'string',
        'pattern' => '^[0-9]+$',
    ];

    $result = $manager->validate('abc', $schema);

    expect($result->isValid())->toBeFalse();
});

it('returns invalid result for minimum constraint violation', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'integer',
        'minimum' => 10,
    ];

    $result = $manager->validate(5, $schema);

    expect($result->isValid())->toBeFalse();
});

it('returns invalid result for maximum constraint violation', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'integer',
        'maximum' => 100,
    ];

    $result = $manager->validate(150, $schema);

    expect($result->isValid())->toBeFalse();
});

// ============================================================================
// validate() Method Tests - Complex Schemas
// ============================================================================

it('validates nested objects', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = [
        'user' => [
            'name' => 'John',
            'address' => [
                'city' => 'NYC',
            ],
        ],
    ];

    $schema = [
        'type' => 'object',
        'properties' => [
            'user' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'address' => [
                        'type' => 'object',
                        'properties' => [
                            'city' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = $manager->validate($data, $schema);

    expect($result->isValid())->toBeTrue();
});

it('validates array items', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = [1, 2, 3, 4, 5];
    $schema = [
        'type' => 'array',
        'items' => ['type' => 'integer'],
    ];

    $result = $manager->validate($data, $schema);

    expect($result->isValid())->toBeTrue();
});

it('validates enum values', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'string',
        'enum' => ['red', 'green', 'blue'],
    ];

    $result = $manager->validate('red', $schema);

    expect($result->isValid())->toBeTrue();
});

it('invalidates non-enum values', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => 'string',
        'enum' => ['red', 'green', 'blue'],
    ];

    $result = $manager->validate('yellow', $schema);

    expect($result->isValid())->toBeFalse();
});

it('validates multiple types', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $schema = [
        'type' => ['string', 'integer'],
    ];

    $result1 = $manager->validate('text', $schema);
    $result2 = $manager->validate(42, $schema);

    expect($result1->isValid())->toBeTrue()
        ->and($result2->isValid())->toBeTrue();
});

// ============================================================================
// validate() Method Tests - Edge Cases
// ============================================================================

it('validates object with properties', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(['key' => 'value'], ['type' => 'object']);

    expect($result->isValid())->toBeTrue();
});

it('validates empty array', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate([], ['type' => 'array']);

    expect($result->isValid())->toBeTrue();
});

it('validates empty string', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate('', ['type' => 'string']);

    expect($result->isValid())->toBeTrue();
});

it('validates zero', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(0, ['type' => 'integer']);

    expect($result->isValid())->toBeTrue();
});

it('validates negative numbers', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(-42, ['type' => 'integer']);

    expect($result->isValid())->toBeTrue();
});

it('validates float numbers', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(3.14, ['type' => 'number']);

    expect($result->isValid())->toBeTrue();
});

it('validates very large numbers', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result = $manager->validate(9_007_199_254_740_991, ['type' => 'integer']);

    expect($result->isValid())->toBeTrue();
});

it('validates with empty schema', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    // Empty schema allows any data
    $result = $manager->validate('anything', []);

    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// validate() Method Tests - Real-world Scenarios
// ============================================================================

it('validates user registration data', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = [
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'age' => 25,
    ];

    $schema = [
        'type' => 'object',
        'properties' => [
            'username' => [
                'type' => 'string',
                'minLength' => 3,
                'maxLength' => 20,
            ],
            'email' => [
                'type' => 'string',
                'format' => 'email',
            ],
            'age' => [
                'type' => 'integer',
                'minimum' => 18,
                'maximum' => 120,
            ],
        ],
        'required' => ['username', 'email'],
    ];

    $result = $manager->validate($data, $schema);

    expect($result->isValid())->toBeTrue();
});

it('validates API response structure', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = [
        'status' => 'success',
        'data' => [
            'id' => 123,
            'name' => 'Product',
        ],
        'meta' => [
            'timestamp' => 1_234_567_890,
        ],
    ];

    $schema = [
        'type' => 'object',
        'properties' => [
            'status' => [
                'type' => 'string',
                'enum' => ['success', 'error'],
            ],
            'data' => ['type' => 'object'],
            'meta' => ['type' => 'object'],
        ],
        'required' => ['status'],
    ];

    $result = $manager->validate($data, $schema);

    expect($result->isValid())->toBeTrue();
});

it('validates configuration object', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $data = [
        'debug' => true,
        'timeout' => 30,
        'retries' => 3,
        'hosts' => ['host1.com', 'host2.com'],
    ];

    $schema = [
        'type' => 'object',
        'properties' => [
            'debug' => ['type' => 'boolean'],
            'timeout' => ['type' => 'integer', 'minimum' => 1],
            'retries' => ['type' => 'integer', 'minimum' => 0],
            'hosts' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'minItems' => 1,
            ],
        ],
    ];

    $result = $manager->validate($data, $schema);

    expect($result->isValid())->toBeTrue();
});

// ============================================================================
// Integration Tests
// ============================================================================

it('can validate multiple schemas in sequence', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result1 = $manager->validate('text', ['type' => 'string']);
    $result2 = $manager->validate(42, ['type' => 'integer']);
    $result3 = $manager->validate(true, ['type' => 'boolean']);

    expect($result1->isValid())->toBeTrue()
        ->and($result2->isValid())->toBeTrue()
        ->and($result3->isValid())->toBeTrue();
});

it('maintains independence between validations', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    // First validation fails
    $result1 = $manager->validate('not a number', ['type' => 'integer']);

    // Second validation succeeds
    $result2 = $manager->validate(42, ['type' => 'integer']);

    expect($result1->isValid())->toBeFalse()
        ->and($result2->isValid())->toBeTrue();
});

it('works with different drafts in same session', function (): void {
    $factory = new ValidatorFactory();
    $manager = new JsonSchemaManager($factory);

    $result1 = $manager->validate('test', ['type' => 'string'], Draft::Draft04);
    $result2 = $manager->validate('test', ['type' => 'string'], Draft::Draft07);
    $result3 = $manager->validate('test', ['type' => 'string'], Draft::Draft202012);

    expect($result1->isValid())->toBeTrue()
        ->and($result2->isValid())->toBeTrue()
        ->and($result3->isValid())->toBeTrue();
});
