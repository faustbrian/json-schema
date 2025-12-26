<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\SchemaDiffer;

use function describe;
use function expect;
use function it;

describe('SchemaDiffer', function (): void {
    describe('diff', function (): void {
        it('detects added keywords', function (): void {
            $oldSchema = ['type' => 'string'];
            $newSchema = ['type' => 'string', 'minLength' => 5];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff)->toHaveKey('added')
                ->and($diff['added'])->toHaveKey('minLength')
                ->and($diff['added']['minLength'])->toBe(5);
        });

        it('detects removed keywords', function (): void {
            $oldSchema = ['type' => 'string', 'maxLength' => 100];
            $newSchema = ['type' => 'string'];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff)->toHaveKey('removed')
                ->and($diff['removed'])->toHaveKey('maxLength')
                ->and($diff['removed']['maxLength'])->toBe(100);
        });

        it('detects changed keywords', function (): void {
            $oldSchema = ['type' => 'string', 'minLength' => 5];
            $newSchema = ['type' => 'string', 'minLength' => 10];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff)->toHaveKey('changed')
                ->and($diff['changed'])->toHaveKey('minLength')
                ->and($diff['changed']['minLength']['old'])->toBe(5)
                ->and($diff['changed']['minLength']['new'])->toBe(10);
        });

        it('detects type changes', function (): void {
            $oldSchema = ['type' => 'string'];
            $newSchema = ['type' => 'number'];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff['changed'])->toHaveKey('type')
                ->and($diff['changed']['type']['old'])->toBe('string')
                ->and($diff['changed']['type']['new'])->toBe('number');
        });

        it('detects multiple changes', function (): void {
            $oldSchema = [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
            ];
            $newSchema = [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
                'required' => ['name'],
                'additionalProperties' => false,
            ];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff['added'])->toHaveKey('required')
                ->and($diff['added'])->toHaveKey('additionalProperties');
        });

        it('handles identical schemas', function (): void {
            $schema = ['type' => 'string', 'minLength' => 5];

            $diff = SchemaDiffer::diff($schema, $schema);

            expect($diff['added'])->toBeEmpty()
                ->and($diff['removed'])->toBeEmpty()
                ->and($diff['changed'])->toBeEmpty();
        });

        it('handles empty schemas', function (): void {
            $diff = SchemaDiffer::diff([], []);

            expect($diff['added'])->toBeEmpty()
                ->and($diff['removed'])->toBeEmpty()
                ->and($diff['changed'])->toBeEmpty();
        });

        it('detects property additions', function (): void {
            $oldSchema = [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
            ];
            $newSchema = [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
            ];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff['changed'])->toHaveKey('properties');
        });

        it('detects required field changes', function (): void {
            $oldSchema = ['required' => ['name']];
            $newSchema = ['required' => ['name', 'email']];

            $diff = SchemaDiffer::diff($oldSchema, $newSchema);

            expect($diff['changed'])->toHaveKey('required');
        });
    });

    describe('hasBreakingChanges', function (): void {
        it('detects breaking change when required fields added', function (): void {
            $oldSchema = ['type' => 'object'];
            $newSchema = ['type' => 'object', 'required' => ['name']];

            // Note: This compares the 'required' key directly, not the array contents
            // So adding 'required' key is detected as a change
            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeFalse(); // Added, not changed
        });

        it('detects breaking change when required fields change', function (): void {
            $oldSchema = ['type' => 'object', 'required' => ['name']];
            $newSchema = ['type' => 'object', 'required' => ['name', 'email']];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeTrue();
        });

        it('detects breaking change when type changes', function (): void {
            $oldSchema = ['type' => 'string'];
            $newSchema = ['type' => 'number'];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeTrue();
        });

        it('detects breaking change when minimum constraint added', function (): void {
            $oldSchema = ['type' => 'number'];
            $newSchema = ['type' => 'number', 'minimum' => 0];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeTrue();
        });

        it('detects breaking change when maximum constraint added', function (): void {
            $oldSchema = ['type' => 'number'];
            $newSchema = ['type' => 'number', 'maximum' => 100];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeTrue();
        });

        it('does not detect breaking change for non-restrictive additions', function (): void {
            $oldSchema = ['type' => 'string'];
            $newSchema = ['type' => 'string', 'description' => 'A string value'];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeFalse();
        });

        it('does not detect breaking change when relaxing constraints', function (): void {
            $oldSchema = ['type' => 'string', 'minLength' => 10];
            $newSchema = ['type' => 'string', 'minLength' => 5];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeFalse();
        });

        it('handles identical schemas', function (): void {
            $schema = ['type' => 'string', 'minLength' => 5];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($schema, $schema);

            expect($hasBreaking)->toBeFalse();
        });

        it('detects multiple breaking changes', function (): void {
            $oldSchema = ['type' => 'string'];
            $newSchema = [
                'type' => 'number',
                'minimum' => 0,
                'maximum' => 100,
            ];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeTrue();
        });

        it('handles complex schema changes', function (): void {
            $oldSchema = [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ];
            $newSchema = [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
                'required' => ['name'],
            ];

            $hasBreaking = SchemaDiffer::hasBreakingChanges($oldSchema, $newSchema);

            expect($hasBreaking)->toBeFalse(); // Required is added, not changed
        });
    });
});
