<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Factories\ValidatorFactory;
use Cline\JsonSchema\Support\SchemaValidator;

use function describe;
use function expect;
use function it;

describe('SchemaValidator', function (): void {
    it('validates valid object schema', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with $schema keyword', function (): void {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'string',
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with explicit draft parameter', function (): void {
        $schema = [
            'type' => 'string',
            'minLength' => 5,
        ];

        $result = SchemaValidator::validateSchema($schema, Draft::Draft07);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with Draft 04', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema, Draft::Draft04);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with Draft 06', function (): void {
        $schema = [
            'type' => 'number',
            'minimum' => 0,
        ];

        $result = SchemaValidator::validateSchema($schema, Draft::Draft06);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with Draft 2019-09', function (): void {
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ];

        $result = SchemaValidator::validateSchema($schema, Draft::Draft201909);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with Draft 2020-12', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema, Draft::Draft202012);

        expect($result->isValid())->toBeTrue();
    });

    it('detects draft from $schema keyword', function (): void {
        $schema = [
            '$schema' => 'https://json-schema.org/draft-07/schema',
            'type' => 'string',
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('defaults to Draft 2020-12 when no $schema present', function (): void {
        $schema = [
            'type' => 'string',
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('isValidSchema returns true for valid schema', function (): void {
        $schema = [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
            ],
        ];

        $isValid = SchemaValidator::isValidSchema($schema);

        expect($isValid)->toBeTrue();
    });

    it('validates complex nested schema', function (): void {
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
                                    'city' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with validator factory', function (): void {
        $schema = [
            'type' => 'string',
            'pattern' => '^[a-z]+$',
        ];
        $factory = new ValidatorFactory();

        $result = SchemaValidator::validateSchema($schema, null, $factory);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with all draft-specific keywords', function (): void {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
            'additionalProperties' => false,
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with allOf composition', function (): void {
        $schema = [
            'allOf' => [
                ['type' => 'object'],
                ['properties' => ['name' => ['type' => 'string']]],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with anyOf composition', function (): void {
        $schema = [
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates schema with oneOf composition', function (): void {
        $schema = [
            'oneOf' => [
                ['type' => 'string', 'maxLength' => 5],
                ['type' => 'number', 'minimum' => 0],
            ],
        ];

        $result = SchemaValidator::validateSchema($schema);

        expect($result->isValid())->toBeTrue();
    });
});
