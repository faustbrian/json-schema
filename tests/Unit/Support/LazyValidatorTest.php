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
use Cline\JsonSchema\Support\LazyValidator;

use function describe;
use function expect;
use function it;

describe('LazyValidator', function (): void {
    it('returns success for valid data', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = ['name' => 'John', 'age' => 30];
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue()
            ->and($result->errors)->toBeEmpty();
    });

    it('returns only first error for invalid data', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = ['name' => 123, 'age' => 'not-a-number'];
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isInvalid())->toBeTrue()
            ->and($result->errors)->toHaveCount(1);
    });

    it('isValid returns true for valid data', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 'test@example.com';
        $schema = [
            'type' => 'string',
            'format' => 'email',
        ];

        $isValid = LazyValidator::isValid($validator, $data, $schema);

        expect($isValid)->toBeTrue();
    });

    it('isValid returns false for invalid data', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft07);

        $data = 'not-an-email';
        $schema = [
            'type' => 'string',
            'format' => 'email',
        ];

        $isValid = LazyValidator::isValid($validator, $data, $schema);

        expect($isValid)->toBeFalse();
    });

    it('works with Draft 07 validator', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft07);

        $data = ['id' => 1, 'name' => 'Product'];
        $schema = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ],
            'required' => ['id', 'name'],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('works with Draft 04 validator', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft04);

        $data = 'https://example.com';
        $schema = [
            'type' => 'string',
            'format' => 'uri',
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates required properties', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = ['name' => 'John'];
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
            ],
            'required' => ['name', 'email'],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isInvalid())->toBeTrue()
            ->and($result->errors)->toHaveCount(1);
    });

    it('validates array items', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = [1, 2, 3];
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates nested objects', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = [
            'user' => [
                'name' => 'John',
                'age' => 30,
            ],
        ];
        $schema = [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'age' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates string format', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = '2024-01-15';
        $schema = [
            'type' => 'string',
            'format' => 'date',
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates number constraints', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 150;
        $schema = [
            'type' => 'integer',
            'minimum' => 0,
            'maximum' => 100,
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isInvalid())->toBeTrue()
            ->and($result->errors)->toHaveCount(1);
    });

    it('validates string length', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 'abc';
        $schema = [
            'type' => 'string',
            'minLength' => 5,
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isInvalid())->toBeTrue()
            ->and($result->errors)->toHaveCount(1);
    });

    it('validates pattern matching', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 'abc123';
        $schema = [
            'type' => 'string',
            'pattern' => '^[0-9]+$',
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isInvalid())->toBeTrue();
    });

    it('validates enum values', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 'blue';
        $schema = [
            'type' => 'string',
            'enum' => ['red', 'green', 'blue'],
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });

    it('validates const values', function (): void {
        $factory = new ValidatorFactory();
        $validator = $factory->create(Draft::Draft202012);

        $data = 'exact-value';
        $schema = [
            'type' => 'string',
            'const' => 'exact-value',
        ];

        $result = LazyValidator::validate($validator, $data, $schema);

        expect($result->isValid())->toBeTrue();
    });
});
