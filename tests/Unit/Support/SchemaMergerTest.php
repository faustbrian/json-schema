<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\SchemaMerger;

use function describe;
use function expect;
use function it;

describe('SchemaMerger', function (): void {
    describe('mergeAllOf', function (): void {
        it('returns null type for empty array', function (): void {
            $result = SchemaMerger::mergeAllOf([]);

            expect($result)->toBe(['type' => 'null']);
        });

        it('returns single schema unchanged', function (): void {
            $schema = ['type' => 'string'];
            $result = SchemaMerger::mergeAllOf([$schema]);

            expect($result)->toBe($schema);
        });

        it('merges two schemas with allOf', function (): void {
            $schema1 = ['type' => 'object'];
            $schema2 = ['properties' => ['name' => ['type' => 'string']]];

            $result = SchemaMerger::mergeAllOf([$schema1, $schema2]);

            expect($result)->toHaveKey('allOf')
                ->and($result['allOf'])->toHaveCount(2)
                ->and($result['allOf'][0])->toBe($schema1)
                ->and($result['allOf'][1])->toBe($schema2);
        });

        it('merges multiple schemas with allOf', function (): void {
            $schemas = [
                ['type' => 'object'],
                ['properties' => ['name' => ['type' => 'string']]],
                ['required' => ['name']],
            ];

            $result = SchemaMerger::mergeAllOf($schemas);

            expect($result['allOf'])->toHaveCount(3);
        });
    });

    describe('mergeAnyOf', function (): void {
        it('returns null type for empty array', function (): void {
            $result = SchemaMerger::mergeAnyOf([]);

            expect($result)->toBe(['type' => 'null']);
        });

        it('returns single schema unchanged', function (): void {
            $schema = ['type' => 'number'];
            $result = SchemaMerger::mergeAnyOf([$schema]);

            expect($result)->toBe($schema);
        });

        it('merges two schemas with anyOf', function (): void {
            $schema1 = ['type' => 'string'];
            $schema2 = ['type' => 'number'];

            $result = SchemaMerger::mergeAnyOf([$schema1, $schema2]);

            expect($result)->toHaveKey('anyOf')
                ->and($result['anyOf'])->toHaveCount(2)
                ->and($result['anyOf'][0])->toBe($schema1)
                ->and($result['anyOf'][1])->toBe($schema2);
        });

        it('merges multiple schemas with anyOf', function (): void {
            $schemas = [
                ['type' => 'string'],
                ['type' => 'number'],
                ['type' => 'boolean'],
            ];

            $result = SchemaMerger::mergeAnyOf($schemas);

            expect($result['anyOf'])->toHaveCount(3);
        });
    });

    describe('mergeOneOf', function (): void {
        it('returns null type for empty array', function (): void {
            $result = SchemaMerger::mergeOneOf([]);

            expect($result)->toBe(['type' => 'null']);
        });

        it('returns single schema unchanged', function (): void {
            $schema = ['type' => 'integer'];
            $result = SchemaMerger::mergeOneOf([$schema]);

            expect($result)->toBe($schema);
        });

        it('merges two schemas with oneOf', function (): void {
            $schema1 = ['type' => 'string', 'maxLength' => 5];
            $schema2 = ['type' => 'number', 'minimum' => 0];

            $result = SchemaMerger::mergeOneOf([$schema1, $schema2]);

            expect($result)->toHaveKey('oneOf')
                ->and($result['oneOf'])->toHaveCount(2)
                ->and($result['oneOf'][0])->toBe($schema1)
                ->and($result['oneOf'][1])->toBe($schema2);
        });

        it('merges multiple schemas with oneOf', function (): void {
            $schemas = [
                ['type' => 'string', 'pattern' => '^[a-z]+$'],
                ['type' => 'number', 'multipleOf' => 5],
                ['type' => 'boolean'],
            ];

            $result = SchemaMerger::mergeOneOf($schemas);

            expect($result['oneOf'])->toHaveCount(3);
        });
    });

    describe('deepMerge', function (): void {
        it('merges properties from both schemas', function (): void {
            $schema1 = [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ];
            $schema2 = [
                'type' => 'object',
                'properties' => [
                    'age' => ['type' => 'integer'],
                ],
            ];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result['properties'])->toHaveKeys(['name', 'age'])
                ->and($result['properties']['name'])->toBe(['type' => 'string'])
                ->and($result['properties']['age'])->toBe(['type' => 'integer']);
        });

        it('merges required arrays', function (): void {
            $schema1 = ['required' => ['name']];
            $schema2 = ['required' => ['email']];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result['required'])->toContain('name')
                ->and($result['required'])->toContain('email')
                ->and($result['required'])->toHaveCount(2);
        });

        it('deduplicates required fields', function (): void {
            $schema1 = ['required' => ['name', 'age']];
            $schema2 = ['required' => ['name', 'email']];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result['required'])->toHaveCount(3)
                ->and($result['required'])->toContain('name')
                ->and($result['required'])->toContain('age')
                ->and($result['required'])->toContain('email');
        });

        it('uses allOf for conflicting values', function (): void {
            $schema1 = ['minimum' => 0];
            $schema2 = ['minimum' => 10];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result)->toHaveKey('allOf')
                ->and($result['allOf'])->toHaveCount(2)
                ->and($result['allOf'][0])->toBe(['minimum' => 0])
                ->and($result['allOf'][1])->toBe(['minimum' => 10]);
        });

        it('keeps same values only once', function (): void {
            $schema1 = ['type' => 'object'];
            $schema2 = ['type' => 'object'];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result)->toBe(['type' => 'object'])
                ->and($result)->not->toHaveKey('allOf');
        });

        it('preserves unique keys from first schema', function (): void {
            $schema1 = ['type' => 'object', 'additionalProperties' => false];
            $schema2 = ['type' => 'object'];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result)->toHaveKey('additionalProperties')
                ->and($result['additionalProperties'])->toBeFalse();
        });

        it('preserves unique keys from second schema', function (): void {
            $schema1 = ['type' => 'object'];
            $schema2 = ['type' => 'object', 'minProperties' => 1];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result)->toHaveKey('minProperties')
                ->and($result['minProperties'])->toBe(1);
        });

        it('merges empty schemas', function (): void {
            $result = SchemaMerger::deepMerge([], []);

            expect($result)->toBe([]);
        });

        it('merges complex nested schemas', function (): void {
            $schema1 = [
                'type' => 'object',
                'properties' => [
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['user'],
            ];
            $schema2 = [
                'type' => 'object',
                'properties' => [
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'email' => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['timestamp'],
            ];

            $result = SchemaMerger::deepMerge($schema1, $schema2);

            expect($result['required'])->toContain('user')
                ->and($result['required'])->toContain('timestamp');
        });
    });
});
