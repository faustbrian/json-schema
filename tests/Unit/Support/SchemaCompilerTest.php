<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\SchemaCompiler;

use function afterEach;
use function beforeEach;
use function describe;
use function expect;
use function it;

describe('SchemaCompiler', function (): void {
    beforeEach(function (): void {
        SchemaCompiler::clearCache();
    });

    afterEach(function (): void {
        SchemaCompiler::clearCache();
    });

    it('compiles schema', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema);
    });

    it('caches compiled schema', function (): void {
        $schema = [
            'type' => 'string',
            'minLength' => 5,
        ];

        SchemaCompiler::compile($schema);

        expect(SchemaCompiler::isCached($schema))->toBeTrue();
    });

    it('returns cached schema on second compile', function (): void {
        $schema = [
            'type' => 'number',
            'minimum' => 0,
        ];

        $first = SchemaCompiler::compile($schema);
        $second = SchemaCompiler::compile($schema);

        expect($second)->toBe($first);
    });

    it('isCached returns false for uncached schema', function (): void {
        $schema = [
            'type' => 'boolean',
        ];

        expect(SchemaCompiler::isCached($schema))->toBeFalse();
    });

    it('clearCache removes all cached schemas', function (): void {
        $schema1 = ['type' => 'string'];
        $schema2 = ['type' => 'number'];

        SchemaCompiler::compile($schema1);
        SchemaCompiler::compile($schema2);

        expect(SchemaCompiler::isCached($schema1))->toBeTrue()
            ->and(SchemaCompiler::isCached($schema2))->toBeTrue();

        SchemaCompiler::clearCache();

        expect(SchemaCompiler::isCached($schema1))->toBeFalse()
            ->and(SchemaCompiler::isCached($schema2))->toBeFalse();
    });

    it('getCacheStats returns cache size', function (): void {
        $schema1 = ['type' => 'string'];
        $schema2 = ['type' => 'number'];

        SchemaCompiler::compile($schema1);
        SchemaCompiler::compile($schema2);

        $stats = SchemaCompiler::getCacheStats();

        expect($stats)->toHaveKey('size')
            ->and($stats['size'])->toBe(2)
            ->and($stats)->toHaveKey('maxSize')
            ->and($stats['maxSize'])->toBe(1_000);
    });

    it('getCacheStats shows empty cache initially', function (): void {
        $stats = SchemaCompiler::getCacheStats();

        expect($stats['size'])->toBe(0)
            ->and($stats['maxSize'])->toBe(1_000);
    });

    it('compiles complex nested schema', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'age' => ['type' => 'integer'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                    ],
                    'required' => ['name'],
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema)
            ->and(SchemaCompiler::isCached($schema))->toBeTrue();
    });

    it('treats different schemas separately', function (): void {
        $schema1 = ['type' => 'string'];
        $schema2 = ['type' => 'number'];

        SchemaCompiler::compile($schema1);
        SchemaCompiler::compile($schema2);

        expect(SchemaCompiler::isCached($schema1))->toBeTrue()
            ->and(SchemaCompiler::isCached($schema2))->toBeTrue();
    });

    it('treats modified schema as different', function (): void {
        $schema1 = ['type' => 'string', 'minLength' => 5];
        $schema2 = ['type' => 'string', 'minLength' => 10];

        SchemaCompiler::compile($schema1);

        expect(SchemaCompiler::isCached($schema1))->toBeTrue()
            ->and(SchemaCompiler::isCached($schema2))->toBeFalse();
    });

    it('compiles empty schema', function (): void {
        $schema = [];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe([])
            ->and(SchemaCompiler::isCached($schema))->toBeTrue();
    });

    it('compiles schema with $ref', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'person' => ['$ref' => '#/definitions/person'],
            ],
            'definitions' => [
                'person' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema)
            ->and(SchemaCompiler::isCached($schema))->toBeTrue();
    });

    it('compiles schema with allOf', function (): void {
        $schema = [
            'allOf' => [
                ['type' => 'object'],
                ['properties' => ['name' => ['type' => 'string']]],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema);
    });

    it('compiles schema with anyOf', function (): void {
        $schema = [
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema);
    });

    it('compiles schema with oneOf', function (): void {
        $schema = [
            'oneOf' => [
                ['type' => 'string', 'maxLength' => 5],
                ['type' => 'number', 'minimum' => 0],
            ],
        ];

        $compiled = SchemaCompiler::compile($schema);

        expect($compiled)->toBe($schema);
    });
});
