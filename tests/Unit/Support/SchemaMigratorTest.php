<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Support\SchemaMigrator;

use function describe;
use function expect;
use function it;

describe('SchemaMigrator', function (): void {
    it('migrates to Draft 2020-12', function (): void {
        $schema = ['type' => 'string'];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['$schema'])->toContain('2020-12');
    });

    it('migrates to Draft 2019-09', function (): void {
        $schema = ['type' => 'number'];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft201909);

        expect($migrated['$schema'])->toContain('2019-09');
    });

    it('migrates to Draft 07', function (): void {
        $schema = ['type' => 'object'];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft07);

        expect($migrated['$schema'])->toContain('draft-07');
    });

    it('migrates to Draft 06', function (): void {
        $schema = ['type' => 'array'];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft06);

        expect($migrated['$schema'])->toContain('draft-06');
    });

    it('migrates to Draft 04', function (): void {
        $schema = ['type' => 'boolean'];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft04);

        expect($migrated['$schema'])->toContain('draft-04');
    });

    it('converts $recursiveRef to $dynamicRef in 2020-12', function (): void {
        $schema = [
            'type' => 'object',
            '$recursiveRef' => '#',
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated)->toHaveKey('$dynamicRef')
            ->and($migrated['$dynamicRef'])->toBe('#')
            ->and($migrated)->not->toHaveKey('$recursiveRef');
    });

    it('converts $recursiveAnchor to $dynamicAnchor in 2020-12', function (): void {
        $schema = [
            'type' => 'object',
            '$recursiveAnchor' => true,
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated)->toHaveKey('$dynamicAnchor')
            ->and($migrated['$dynamicAnchor'])->toBeTrue()
            ->and($migrated)->not->toHaveKey('$recursiveAnchor');
    });

    it('converts definitions to $defs in 2019-09', function (): void {
        $schema = [
            'type' => 'object',
            'definitions' => [
                'person' => ['type' => 'object'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft201909);

        expect($migrated)->toHaveKey('$defs')
            ->and($migrated['$defs'])->toBe(['person' => ['type' => 'object']])
            ->and($migrated)->not->toHaveKey('definitions');
    });

    it('converts $defs to definitions in Draft 07', function (): void {
        $schema = [
            'type' => 'object',
            '$defs' => [
                'address' => ['type' => 'object'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft07);

        expect($migrated)->toHaveKey('definitions')
            ->and($migrated['definitions'])->toBe(['address' => ['type' => 'object']])
            ->and($migrated)->not->toHaveKey('$defs');
    });

    it('converts $defs to definitions in Draft 06', function (): void {
        $schema = [
            'type' => 'object',
            '$defs' => [
                'user' => ['type' => 'object'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft06);

        expect($migrated)->toHaveKey('definitions')
            ->and($migrated)->not->toHaveKey('$defs');
    });

    it('converts $defs to definitions in Draft 04', function (): void {
        $schema = [
            'type' => 'object',
            '$defs' => [
                'product' => ['type' => 'object'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft04);

        expect($migrated)->toHaveKey('definitions')
            ->and($migrated)->not->toHaveKey('$defs');
    });

    it('preserves schema content during migration', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
            'required' => ['name'],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['type'])->toBe('object')
            ->and($migrated['properties'])->toBe($schema['properties'])
            ->and($migrated['required'])->toBe($schema['required']);
    });

    it('handles empty schema', function (): void {
        $schema = [];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated)->toHaveKey('$schema');
    });

    it('handles complex nested schema', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'addresses' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'street' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft07);

        expect($migrated['type'])->toBe('object')
            ->and($migrated['properties']['user']['properties']['addresses']['items']['properties']['street']['type'])->toBe('string');
    });

    it('migrates schema with allOf', function (): void {
        $schema = [
            'allOf' => [
                ['type' => 'object'],
                ['properties' => ['name' => ['type' => 'string']]],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['allOf'])->toHaveCount(2);
    });

    it('migrates schema with anyOf', function (): void {
        $schema = [
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft201909);

        expect($migrated['anyOf'])->toHaveCount(2);
    });

    it('migrates schema with oneOf', function (): void {
        $schema = [
            'oneOf' => [
                ['type' => 'string', 'maxLength' => 5],
                ['type' => 'number', 'minimum' => 0],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft07);

        expect($migrated['oneOf'])->toHaveCount(2);
    });

    it('migrates schema with $ref', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'person' => ['$ref' => '#/definitions/person'],
            ],
            'definitions' => [
                'person' => ['type' => 'object'],
            ],
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['properties']['person']['$ref'])->toBe('#/definitions/person');
    });

    it('preserves existing $schema during migration', function (): void {
        $schema = [
            '$schema' => 'https://json-schema.org/draft-07/schema',
            'type' => 'string',
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['$schema'])->toContain('2020-12')
            ->and($migrated['$schema'])->not->toContain('draft-07');
    });

    it('handles schema with format validators', function (): void {
        $schema = [
            'type' => 'string',
            'format' => 'email',
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft07);

        expect($migrated['format'])->toBe('email');
    });

    it('handles schema with pattern', function (): void {
        $schema = [
            'type' => 'string',
            'pattern' => '^[a-z]+$',
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft202012);

        expect($migrated['pattern'])->toBe('^[a-z]+$');
    });

    it('handles schema with numeric constraints', function (): void {
        $schema = [
            'type' => 'number',
            'minimum' => 0,
            'maximum' => 100,
            'multipleOf' => 5,
        ];

        $migrated = SchemaMigrator::migrate($schema, Draft::Draft06);

        expect($migrated['minimum'])->toBe(0)
            ->and($migrated['maximum'])->toBe(100)
            ->and($migrated['multipleOf'])->toBe(5);
    });
});
