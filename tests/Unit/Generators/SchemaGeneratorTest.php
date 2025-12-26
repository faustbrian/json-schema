<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Generators;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Generators\SchemaGenerator;

use function describe;
use function expect;
use function it;

describe('SchemaGenerator', function (): void {
    it('generates schema for null', function (): void {
        $schema = SchemaGenerator::generate(null);

        expect($schema->toArray())->toHaveKey('type')
            ->and($schema->toArray()['type'])->toBe('null')
            ->and($schema->toArray())->toHaveKey('$schema');
    });

    it('generates schema for boolean', function (): void {
        $schema = SchemaGenerator::generate(true);

        expect($schema->toArray()['type'])->toBe('boolean');
    });

    it('generates schema for integer', function (): void {
        $schema = SchemaGenerator::generate(42);

        expect($schema->toArray()['type'])->toBe('integer');
    });

    it('generates schema for float', function (): void {
        $schema = SchemaGenerator::generate(3.14);

        expect($schema->toArray()['type'])->toBe('number');
    });

    it('generates schema for string', function (): void {
        $schema = SchemaGenerator::generate('hello');

        expect($schema->toArray()['type'])->toBe('string');
    });

    it('generates schema for email string', function (): void {
        $schema = SchemaGenerator::generate('test@example.com');

        expect($schema->toArray()['type'])->toBe('string')
            ->and($schema->toArray()['format'])->toBe('email');
    });

    it('generates schema for URI string', function (): void {
        $schema = SchemaGenerator::generate('https://example.com');

        expect($schema->toArray()['type'])->toBe('string')
            ->and($schema->toArray()['format'])->toBe('uri');
    });

    it('generates schema for date string', function (): void {
        $schema = SchemaGenerator::generate('2024-01-15');

        expect($schema->toArray()['type'])->toBe('string')
            ->and($schema->toArray()['format'])->toBe('date');
    });

    it('generates schema for date-time string', function (): void {
        $schema = SchemaGenerator::generate('2024-01-15T10:30:00Z');

        expect($schema->toArray()['type'])->toBe('string')
            ->and($schema->toArray()['format'])->toBe('date-time');
    });

    it('generates schema for UUID string', function (): void {
        $schema = SchemaGenerator::generate('550e8400-e29b-41d4-a716-446655440000');

        expect($schema->toArray()['type'])->toBe('string')
            ->and($schema->toArray()['format'])->toBe('uuid');
    });

    it('generates schema for empty array', function (): void {
        $schema = SchemaGenerator::generate([]);

        expect($schema->toArray()['type'])->toBe('array');
    });

    it('generates schema for array with items', function (): void {
        $schema = SchemaGenerator::generate([1, 2, 3]);

        expect($schema->toArray()['type'])->toBe('array')
            ->and($schema->toArray())->toHaveKey('items')
            ->and($schema->toArray()['items']['type'])->toBe('integer');
    });

    it('generates schema for object', function (): void {
        $schema = SchemaGenerator::generate(['name' => 'John', 'age' => 30]);

        expect($schema->toArray()['type'])->toBe('object')
            ->and($schema->toArray())->toHaveKey('properties')
            ->and($schema->toArray()['properties'])->toHaveKey('name')
            ->and($schema->toArray()['properties'])->toHaveKey('age')
            ->and($schema->toArray())->toHaveKey('required')
            ->and($schema->toArray()['required'])->toContain('name')
            ->and($schema->toArray()['required'])->toContain('age');
    });

    it('generates schema for nested object', function (): void {
        $schema = SchemaGenerator::generate([
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ]);

        expect($schema->toArray()['type'])->toBe('object')
            ->and($schema->toArray()['properties']['user']['type'])->toBe('object')
            ->and($schema->toArray()['properties']['user']['properties'])->toHaveKey('name')
            ->and($schema->toArray()['properties']['user']['properties'])->toHaveKey('email');
    });

    it('generates schema with specified draft', function (): void {
        $schema = SchemaGenerator::generate('test', Draft::Draft07);

        expect($schema->toArray()['$schema'])->toContain('draft-07');
    });

    it('generates schema from multiple samples', function (): void {
        $schema = SchemaGenerator::generateFromSamples([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        expect($schema->toArray()['type'])->toBe('object');
    });

    it('generates schema from empty samples array', function (): void {
        $schema = SchemaGenerator::generateFromSamples([]);

        expect($schema->toArray()['type'])->toBe('null');
    });

    it('generates schema for mixed type array', function (): void {
        $schema = SchemaGenerator::generate([1, 'two', true]);

        expect($schema->toArray()['type'])->toBe('array')
            ->and($schema->toArray()['items'])->toHaveKey('anyOf');
    });

    it('generates schema for complex nested structure', function (): void {
        $data = [
            'id' => 123,
            'name' => 'Product',
            'price' => 99.99,
            'tags' => ['sale', 'featured'],
            'metadata' => [
                'created' => '2024-01-15',
                'updated' => '2024-01-16',
            ],
        ];

        $schema = SchemaGenerator::generate($data);

        expect($schema->toArray()['type'])->toBe('object')
            ->and($schema->toArray()['properties'])->toHaveKeys(['id', 'name', 'price', 'tags', 'metadata'])
            ->and($schema->toArray()['properties']['tags']['type'])->toBe('array')
            ->and($schema->toArray()['properties']['metadata']['type'])->toBe('object');
    });

    it('defaults to Draft 2020-12', function (): void {
        $schema = SchemaGenerator::generate('test');

        expect($schema->toArray()['$schema'])->toContain('2020-12');
    });
});
