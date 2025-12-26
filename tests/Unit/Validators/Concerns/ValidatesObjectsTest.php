<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Concerns;

use Cline\JsonSchema\Support\JsonDecoder;
use Cline\JsonSchema\Validators\Draft202012Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use function expect;
use function it;

// required Tests

it('validates required with all properties present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'required' => ['name', 'age'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails required when one property is missing', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'required' => ['name', 'age'],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
    expect($result->errors())->not->toBeEmpty();
});

it('fails required when all properties are missing', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'required' => ['name', 'age'],
    ]);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates required with extra properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'required' => ['name'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30, 'city' => 'NYC'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when required is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not object for required', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['required' => ['name']]);

    $result = $validator->validate([1, 2, 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// minProperties Tests

it('validates minProperties with exact count', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minProperties' => 2]);

    $result = $validator->validate(['a' => 1, 'b' => 2], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minProperties with more than minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minProperties' => 2]);

    $result = $validator->validate(['a' => 1, 'b' => 2, 'c' => 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails minProperties with fewer than minimum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minProperties' => 3]);

    $result = $validator->validate(['a' => 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minProperties with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minProperties' => 0]);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when minProperties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// maxProperties Tests

it('validates maxProperties with exact count', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxProperties' => 2]);

    $result = $validator->validate(['a' => 1, 'b' => 2], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maxProperties with fewer than maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxProperties' => 3]);

    $result = $validator->validate(['a' => 1], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails maxProperties with more than maximum', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxProperties' => 2]);

    $result = $validator->validate(['a' => 1, 'b' => 2, 'c' => 3], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maxProperties with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxProperties' => 0]);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when maxProperties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['a' => 1, 'b' => 2, 'c' => 3], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// properties Tests

it('validates properties with matching types', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails properties when type does not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 'thirty'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates properties with missing optional properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates properties with extra properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when properties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// additionalProperties Tests

it('validates additionalProperties with schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'additionalProperties' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails additionalProperties when additional property does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'additionalProperties' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 'thirty'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails additionalProperties when false and additional properties exist', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'additionalProperties' => false,
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates additionalProperties when false and no additional properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'additionalProperties' => false,
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates additionalProperties when true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'additionalProperties' => true,
    ]);

    $result = $validator->validate(['name' => 'John', 'anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates additionalProperties with patternProperties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'patternProperties' => [
            '^age' => ['type' => 'number'],
        ],
        'additionalProperties' => false,
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when additionalProperties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// patternProperties Tests

it('validates patternProperties with matching pattern', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'patternProperties' => [
            '^num' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['num1' => 10, 'num2' => 20], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails patternProperties when value does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'patternProperties' => [
            '^num' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['num1' => 'not-a-number'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates patternProperties with multiple patterns', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'patternProperties' => [
            '^str' => ['type' => 'string'],
            '^num' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['str1' => 'hello', 'num1' => 42], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates patternProperties with non-matching properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'patternProperties' => [
            '^num' => ['type' => 'number'],
        ],
    ]);

    $result = $validator->validate(['num1' => 10, 'name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when patternProperties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// propertyNames Tests

it('validates propertyNames with all names matching schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'propertyNames' => ['pattern' => '^[a-z]+$'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails propertyNames when one name does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'propertyNames' => ['pattern' => '^[a-z]+$'],
    ]);

    $result = $validator->validate(['name' => 'John', 'Age' => 30], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates propertyNames with minLength', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'propertyNames' => ['minLength' => 3],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(['id' => 1], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates propertyNames with empty object', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'propertyNames' => ['pattern' => '^[a-z]+$'],
    ]);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when propertyNames is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['AnyCase' => 'allowed'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// dependencies Tests (legacy)

it('validates dependencies with property dependency', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234', 'billing_address' => 'NYC'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails dependencies when dependent property is missing', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates dependencies when trigger property is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates dependencies with schema dependency', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => [
                'properties' => [
                    'billing_address' => ['type' => 'string'],
                ],
                'required' => ['billing_address'],
            ],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234', 'billing_address' => 'NYC'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates dependencies with boolean schema true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => true,
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates dependencies with boolean schema false', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependencies' => [
            'credit_card' => false,
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when dependencies is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// dependentRequired Tests (Draft 2019-09+)

it('validates dependentRequired with dependent property present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentRequired' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234', 'billing_address' => 'NYC'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails dependentRequired when dependent property is missing', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentRequired' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates dependentRequired when trigger property is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentRequired' => [
            'credit_card' => ['billing_address'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when dependentRequired is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// dependentSchemas Tests (Draft 2019-09+)

it('validates dependentSchemas with matching schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentSchemas' => [
            'credit_card' => [
                'properties' => [
                    'billing_address' => ['type' => 'string'],
                ],
                'required' => ['billing_address'],
            ],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234', 'billing_address' => 'NYC'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails dependentSchemas when schema does not match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentSchemas' => [
            'credit_card' => [
                'required' => ['billing_address'],
            ],
        ],
    ]);

    $result = $validator->validate(['credit_card' => '1234'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates dependentSchemas when trigger property is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'dependentSchemas' => [
            'credit_card' => [
                'required' => ['billing_address'],
            ],
        ],
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when dependentSchemas is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'object']);

    $result = $validator->validate(['anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// unevaluatedProperties Tests

it('validates unevaluatedProperties with all properties evaluated', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'unevaluatedProperties' => false,
    ]);

    $result = $validator->validate(['name' => 'John'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails unevaluatedProperties when false and unevaluated properties exist', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'unevaluatedProperties' => false,
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates unevaluatedProperties with schema for unevaluated properties', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'unevaluatedProperties' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails unevaluatedProperties when unevaluated property does not match schema', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'unevaluatedProperties' => ['type' => 'number'],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 'thirty'], $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates unevaluatedProperties when true', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
        'unevaluatedProperties' => true,
    ]);

    $result = $validator->validate(['name' => 'John', 'anything' => 'goes'], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates unevaluatedProperties with empty object', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'unevaluatedProperties' => false,
    ]);

    $result = $validator->validate(JsonDecoder::decode('{}'), $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when unevaluatedProperties is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'properties' => [
            'name' => ['type' => 'string'],
        ],
    ]);

    $result = $validator->validate(['name' => 'John', 'age' => 30], $schema->toArray());
    expect($result->isValid())->toBeTrue();
});
