<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\SchemaRegistry;
use Cline\JsonSchema\ValueObjects\Schema;

use function expect;
use function it;

it('registers a schema successfully', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'string']);
    $uri = 'https://example.com/schemas/string.json';

    // Act
    $registry->register($uri, $schema);

    // Assert
    expect($registry->has($uri))->toBeTrue();
});

it('retrieves a registered schema', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'object', 'properties' => ['name' => ['type' => 'string']]]);
    $uri = 'https://example.com/schemas/user.json';

    // Act
    $registry->register($uri, $schema);
    $retrieved = $registry->get($uri);

    // Assert
    expect($retrieved)->toBe($schema)
        ->and($retrieved->toArray())->toBe(['type' => 'object', 'properties' => ['name' => ['type' => 'string']]]);
});

it('returns null for unregistered schema', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $uri = 'https://example.com/schemas/nonexistent.json';

    // Act
    $result = $registry->get($uri);

    // Assert
    expect($result)->toBeNull();
});

it('checks if schema is registered using has()', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'number']);
    $registeredUri = 'https://example.com/schemas/number.json';
    $unregisteredUri = 'https://example.com/schemas/other.json';

    // Act
    $registry->register($registeredUri, $schema);

    // Assert
    expect($registry->has($registeredUri))->toBeTrue()
        ->and($registry->has($unregisteredUri))->toBeFalse();
});

it('overwrites existing schema with same URI', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $firstSchema = new Schema(['type' => 'string']);
    $secondSchema = new Schema(['type' => 'number']);
    $uri = 'https://example.com/schemas/overwrite.json';

    // Act
    $registry->register($uri, $firstSchema);
    $registry->register($uri, $secondSchema);

    $retrieved = $registry->get($uri);

    // Assert
    expect($retrieved)->toBe($secondSchema)
        ->and($retrieved->toArray())->toBe(['type' => 'number']);
});

it('removes a registered schema', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'boolean']);
    $uri = 'https://example.com/schemas/boolean.json';

    // Act
    $registry->register($uri, $schema);
    expect($registry->has($uri))->toBeTrue();

    $registry->remove($uri);

    // Assert
    expect($registry->has($uri))->toBeFalse()
        ->and($registry->get($uri))->toBeNull();
});

it('handles removing non-existent schema gracefully', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $uri = 'https://example.com/schemas/never-registered.json';

    // Act
    $registry->remove($uri);

    // Assert
    expect($registry->has($uri))->toBeFalse();
});

it('clears all registered schemas', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $uri1 = 'https://example.com/schemas/first.json';
    $uri2 = 'https://example.com/schemas/second.json';
    $uri3 = 'https://example.com/schemas/third.json';

    $registry->register($uri1, new Schema(['type' => 'string']));
    $registry->register($uri2, new Schema(['type' => 'number']));
    $registry->register($uri3, new Schema(['type' => 'boolean']));

    // Act
    $registry->clear();

    // Assert
    expect($registry->has($uri1))->toBeFalse()
        ->and($registry->has($uri2))->toBeFalse()
        ->and($registry->has($uri3))->toBeFalse()
        ->and($registry->get($uri1))->toBeNull()
        ->and($registry->get($uri2))->toBeNull()
        ->and($registry->get($uri3))->toBeNull();
});

it('handles multiple schemas with different URIs', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $stringSchema = new Schema(['type' => 'string']);
    $numberSchema = new Schema(['type' => 'number']);
    $objectSchema = new Schema(['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]]);

    $stringUri = 'https://example.com/schemas/string.json';
    $numberUri = 'https://example.com/schemas/number.json';
    $objectUri = 'https://example.com/schemas/object.json';

    // Act
    $registry->register($stringUri, $stringSchema);
    $registry->register($numberUri, $numberSchema);
    $registry->register($objectUri, $objectSchema);

    // Assert
    expect($registry->get($stringUri))->toBe($stringSchema)
        ->and($registry->get($numberUri))->toBe($numberSchema)
        ->and($registry->get($objectUri))->toBe($objectSchema)
        ->and($registry->has($stringUri))->toBeTrue()
        ->and($registry->has($numberUri))->toBeTrue()
        ->and($registry->has($objectUri))->toBeTrue();
});

it('handles empty string URI', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'null']);
    $uri = '';

    // Act
    $registry->register($uri, $schema);

    // Assert
    expect($registry->has($uri))->toBeTrue()
        ->and($registry->get($uri))->toBe($schema);
});

it('handles URI with fragments', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'array', 'items' => ['type' => 'string']]);
    $uri = 'https://example.com/schemas/base.json#/definitions/stringArray';

    // Act
    $registry->register($uri, $schema);

    // Assert
    expect($registry->has($uri))->toBeTrue()
        ->and($registry->get($uri))->toBe($schema);
});

it('maintains isolation between different registry instances', function (): void {
    // Arrange
    $registry1 = new SchemaRegistry();
    $registry2 = new SchemaRegistry();
    $schema = new Schema(['type' => 'string']);
    $uri = 'https://example.com/schemas/isolated.json';

    // Act
    $registry1->register($uri, $schema);

    // Assert
    expect($registry1->has($uri))->toBeTrue()
        ->and($registry2->has($uri))->toBeFalse();
});

it('preserves schema identity after registration', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1],
            'age' => ['type' => 'integer', 'minimum' => 0],
            'email' => ['type' => 'string', 'format' => 'email'],
        ],
        'required' => ['name', 'email'],
    ]);
    $uri = 'https://example.com/schemas/person.json';

    // Act
    $registry->register($uri, $schema);
    $retrieved = $registry->get($uri);

    // Assert
    expect($retrieved)->toBe($schema)
        ->and($retrieved->get('type'))->toBe('object')
        ->and($retrieved->get('required'))->toBe(['name', 'email']);
});

it('handles complex schema URIs with query parameters', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema = new Schema(['type' => 'integer']);
    $uri = 'https://example.com/schemas/number.json?version=2.0&draft=2020-12';

    // Act
    $registry->register($uri, $schema);

    // Assert
    expect($registry->has($uri))->toBeTrue()
        ->and($registry->get($uri))->toBe($schema);
});

it('distinguishes between similar URIs', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schema1 = new Schema(['type' => 'string']);
    $schema2 = new Schema(['type' => 'number']);
    $uri1 = 'https://example.com/schemas/test.json';
    $uri2 = 'https://example.com/schemas/test.json#fragment';

    // Act
    $registry->register($uri1, $schema1);
    $registry->register($uri2, $schema2);

    // Assert
    expect($registry->get($uri1))->toBe($schema1)
        ->and($registry->get($uri2))->toBe($schema2)
        ->and($registry->get($uri1))->not->toBe($schema2)
        ->and($registry->get($uri2))->not->toBe($schema1);
});

it('handles registration and retrieval in sequence', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $schemas = [
        'https://example.com/schemas/1.json' => new Schema(['type' => 'string']),
        'https://example.com/schemas/2.json' => new Schema(['type' => 'number']),
        'https://example.com/schemas/3.json' => new Schema(['type' => 'boolean']),
    ];

    // Act & Assert
    foreach ($schemas as $uri => $schema) {
        $registry->register($uri, $schema);
        expect($registry->has($uri))->toBeTrue()
            ->and($registry->get($uri))->toBe($schema);
    }
});

it('clear does not affect empty registry', function (): void {
    // Arrange
    $registry = new SchemaRegistry();

    // Act
    $registry->clear();

    // Assert
    expect($registry->has('any-uri'))->toBeFalse()
        ->and($registry->get('any-uri'))->toBeNull();
});

it('can re-register after removal', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $firstSchema = new Schema(['type' => 'string']);
    $secondSchema = new Schema(['type' => 'number']);
    $uri = 'https://example.com/schemas/reregister.json';

    // Act
    $registry->register($uri, $firstSchema);
    $registry->remove($uri);
    $registry->register($uri, $secondSchema);

    // Assert
    expect($registry->has($uri))->toBeTrue()
        ->and($registry->get($uri))->toBe($secondSchema)
        ->and($registry->get($uri))->not->toBe($firstSchema);
});

it('can re-register after clear', function (): void {
    // Arrange
    $registry = new SchemaRegistry();
    $uri = 'https://example.com/schemas/post-clear.json';
    $schema = new Schema(['type' => 'array']);

    // Act
    $registry->register($uri, new Schema(['type' => 'string']));
    $registry->clear();
    $registry->register($uri, $schema);

    // Assert
    expect($registry->has($uri))->toBeTrue()
        ->and($registry->get($uri))->toBe($schema);
});
