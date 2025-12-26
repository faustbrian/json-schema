<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Builders;

use Cline\JsonSchema\Builders\SchemaBuilder;
use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Enums\Format;
use Cline\JsonSchema\Enums\SchemaType;
use Cline\JsonSchema\ValueObjects\Schema;

use const PHP_INT_MAX;

use function expect;
use function it;

// Static Factory Method
it('creates new builder instance via static create method', function (): void {
    // Arrange & Act
    $builder = SchemaBuilder::create();

    // Assert
    expect($builder)->toBeInstanceOf(SchemaBuilder::class)
        ->and($builder->toArray())->toBe([]);
});

// Draft Version
it('sets schema draft version', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->draft(Draft::Draft07)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('$schema')
        ->and($schema['$schema'])->toBe('http://json-schema.org/draft-07/schema#');
});

it('sets multiple draft versions', function (): void {
    // Arrange & Act
    $draft04 = SchemaBuilder::create()->draft(Draft::Draft04)->toArray();
    $draft06 = SchemaBuilder::create()->draft(Draft::Draft06)->toArray();
    $draft07 = SchemaBuilder::create()->draft(Draft::Draft07)->toArray();
    $draft201909 = SchemaBuilder::create()->draft(Draft::Draft201909)->toArray();
    $draft202012 = SchemaBuilder::create()->draft(Draft::Draft202012)->toArray();

    // Assert
    expect($draft04['$schema'])->toBe('http://json-schema.org/draft-04/schema#')
        ->and($draft06['$schema'])->toBe('http://json-schema.org/draft-06/schema#')
        ->and($draft07['$schema'])->toBe('http://json-schema.org/draft-07/schema#')
        ->and($draft201909['$schema'])->toBe('https://json-schema.org/draft/2019-09/schema')
        ->and($draft202012['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
});

// Type Methods
it('sets single schema type', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::String)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('type')
        ->and($schema['type'])->toBe('string');
});

it('sets all primitive schema types', function (): void {
    // Arrange & Act
    $null = SchemaBuilder::create()->type(SchemaType::Null)->toArray();
    $boolean = SchemaBuilder::create()->type(SchemaType::Boolean)->toArray();
    $object = SchemaBuilder::create()->type(SchemaType::Object)->toArray();
    $array = SchemaBuilder::create()->type(SchemaType::Array)->toArray();
    $number = SchemaBuilder::create()->type(SchemaType::Number)->toArray();
    $string = SchemaBuilder::create()->type(SchemaType::String)->toArray();
    $integer = SchemaBuilder::create()->type(SchemaType::Integer)->toArray();

    // Assert
    expect($null['type'])->toBe('null')
        ->and($boolean['type'])->toBe('boolean')
        ->and($object['type'])->toBe('object')
        ->and($array['type'])->toBe('array')
        ->and($number['type'])->toBe('number')
        ->and($string['type'])->toBe('string')
        ->and($integer['type'])->toBe('integer');
});

it('sets multiple allowed types', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->types([SchemaType::String, SchemaType::Null])
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('type')
        ->and($schema['type'])->toBe(['string', 'null']);
});

it('sets multiple types with all primitives', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->types([
            SchemaType::String,
            SchemaType::Number,
            SchemaType::Boolean,
        ])
        ->toArray();

    // Assert
    expect($schema['type'])->toBe(['string', 'number', 'boolean']);
});

// Metadata
it('sets title', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->title('User Schema')
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('title')
        ->and($schema['title'])->toBe('User Schema');
});

it('sets description', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->description('A schema for user data validation')
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('description')
        ->and($schema['description'])->toBe('A schema for user data validation');
});

// Enum and Const
it('sets enum values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->enum(['red', 'green', 'blue'])
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('enum')
        ->and($schema['enum'])->toBe(['red', 'green', 'blue']);
});

it('sets enum with numeric values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->enum([1, 2, 3, 5, 8, 13])
        ->toArray();

    // Assert
    expect($schema['enum'])->toBe([1, 2, 3, 5, 8, 13]);
});

it('sets enum with mixed types', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->enum(['string', 42, true, null])
        ->toArray();

    // Assert
    expect($schema['enum'])->toBe(['string', 42, true, null]);
});

it('sets const value with string', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->const('fixed-value')
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('const')
        ->and($schema['const'])->toBe('fixed-value');
});

it('sets const value with numeric types', function (): void {
    // Arrange & Act
    $integer = SchemaBuilder::create()->const(42)->toArray();
    $float = SchemaBuilder::create()->const(3.14)->toArray();

    // Assert
    expect($integer['const'])->toBe(42)
        ->and($float['const'])->toBe(3.14);
});

it('sets const value with boolean and null', function (): void {
    // Arrange & Act
    $boolean = SchemaBuilder::create()->const(true)->toArray();
    $null = SchemaBuilder::create()->const(null)->toArray();

    // Assert
    expect($boolean['const'])->toBe(true)
        ->and($null['const'])->toBe(null);
});

// Numeric Constraints
it('sets minimum value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(10)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('minimum')
        ->and($schema['minimum'])->toBe(10);
});

it('sets minimum with float value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(10.5)
        ->toArray();

    // Assert
    expect($schema['minimum'])->toBe(10.5);
});

it('sets maximum value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->maximum(100)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('maximum')
        ->and($schema['maximum'])->toBe(100);
});

it('sets maximum with float value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->maximum(99.99)
        ->toArray();

    // Assert
    expect($schema['maximum'])->toBe(99.99);
});

it('sets exclusive minimum value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->exclusiveMinimum(0)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('exclusiveMinimum')
        ->and($schema['exclusiveMinimum'])->toBe(0);
});

it('sets exclusive minimum with float value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->exclusiveMinimum(0.01)
        ->toArray();

    // Assert
    expect($schema['exclusiveMinimum'])->toBe(0.01);
});

it('sets exclusive maximum value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->exclusiveMaximum(100)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('exclusiveMaximum')
        ->and($schema['exclusiveMaximum'])->toBe(100);
});

it('sets exclusive maximum with float value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->exclusiveMaximum(99.99)
        ->toArray();

    // Assert
    expect($schema['exclusiveMaximum'])->toBe(99.99);
});

it('sets multipleOf constraint', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->multipleOf(5)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('multipleOf')
        ->and($schema['multipleOf'])->toBe(5);
});

it('sets multipleOf with decimal value', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->multipleOf(0.01)
        ->toArray();

    // Assert
    expect($schema['multipleOf'])->toBe(0.01);
});

it('sets combined numeric constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(0)
        ->maximum(100)
        ->multipleOf(5)
        ->toArray();

    // Assert
    expect($schema['minimum'])->toBe(0)
        ->and($schema['maximum'])->toBe(100)
        ->and($schema['multipleOf'])->toBe(5);
});

// String Constraints
it('sets minLength', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minLength(5)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('minLength')
        ->and($schema['minLength'])->toBe(5);
});

it('sets minLength to zero', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minLength(0)
        ->toArray();

    // Assert
    expect($schema['minLength'])->toBe(0);
});

it('sets maxLength', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->maxLength(50)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('maxLength')
        ->and($schema['maxLength'])->toBe(50);
});

it('sets combined string length constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minLength(5)
        ->maxLength(50)
        ->toArray();

    // Assert
    expect($schema['minLength'])->toBe(5)
        ->and($schema['maxLength'])->toBe(50);
});

it('sets pattern constraint', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->pattern('^[A-Z][a-z]+$')
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('pattern')
        ->and($schema['pattern'])->toBe('^[A-Z][a-z]+$');
});

it('sets email pattern', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->pattern('^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$')
        ->toArray();

    // Assert
    expect($schema['pattern'])->toBe('^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$');
});

it('sets format constraint', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->format(Format::Email)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('format')
        ->and($schema['format'])->toBe('email');
});

it('sets all date-time format types', function (): void {
    // Arrange & Act
    $dateTime = SchemaBuilder::create()->format(Format::DateTime)->toArray();
    $date = SchemaBuilder::create()->format(Format::Date)->toArray();
    $time = SchemaBuilder::create()->format(Format::Time)->toArray();
    $duration = SchemaBuilder::create()->format(Format::Duration)->toArray();

    // Assert
    expect($dateTime['format'])->toBe('date-time')
        ->and($date['format'])->toBe('date')
        ->and($time['format'])->toBe('time')
        ->and($duration['format'])->toBe('duration');
});

it('sets email format types', function (): void {
    // Arrange & Act
    $email = SchemaBuilder::create()->format(Format::Email)->toArray();
    $idnEmail = SchemaBuilder::create()->format(Format::IdnEmail)->toArray();

    // Assert
    expect($email['format'])->toBe('email')
        ->and($idnEmail['format'])->toBe('idn-email');
});

it('sets hostname format types', function (): void {
    // Arrange & Act
    $hostname = SchemaBuilder::create()->format(Format::Hostname)->toArray();
    $idnHostname = SchemaBuilder::create()->format(Format::IdnHostname)->toArray();

    // Assert
    expect($hostname['format'])->toBe('hostname')
        ->and($idnHostname['format'])->toBe('idn-hostname');
});

it('sets IP address format types', function (): void {
    // Arrange & Act
    $ipv4 = SchemaBuilder::create()->format(Format::Ipv4)->toArray();
    $ipv6 = SchemaBuilder::create()->format(Format::Ipv6)->toArray();

    // Assert
    expect($ipv4['format'])->toBe('ipv4')
        ->and($ipv6['format'])->toBe('ipv6');
});

it('sets URI format types', function (): void {
    // Arrange & Act
    $uri = SchemaBuilder::create()->format(Format::Uri)->toArray();
    $uriRef = SchemaBuilder::create()->format(Format::UriReference)->toArray();
    $iri = SchemaBuilder::create()->format(Format::Iri)->toArray();
    $iriRef = SchemaBuilder::create()->format(Format::IriReference)->toArray();
    $uriTemplate = SchemaBuilder::create()->format(Format::UriTemplate)->toArray();

    // Assert
    expect($uri['format'])->toBe('uri')
        ->and($uriRef['format'])->toBe('uri-reference')
        ->and($iri['format'])->toBe('iri')
        ->and($iriRef['format'])->toBe('iri-reference')
        ->and($uriTemplate['format'])->toBe('uri-template');
});

it('sets JSON Pointer format types', function (): void {
    // Arrange & Act
    $jsonPointer = SchemaBuilder::create()->format(Format::JsonPointer)->toArray();
    $relativeJsonPointer = SchemaBuilder::create()->format(Format::RelativeJsonPointer)->toArray();

    // Assert
    expect($jsonPointer['format'])->toBe('json-pointer')
        ->and($relativeJsonPointer['format'])->toBe('relative-json-pointer');
});

it('sets regex and uuid format types', function (): void {
    // Arrange & Act
    $regex = SchemaBuilder::create()->format(Format::Regex)->toArray();
    $uuid = SchemaBuilder::create()->format(Format::Uuid)->toArray();

    // Assert
    expect($regex['format'])->toBe('regex')
        ->and($uuid['format'])->toBe('uuid');
});

// Object Constraints
it('sets properties with array schemas', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->properties([
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
        ])
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('properties')
        ->and($schema['properties'])->toHaveKey('name')
        ->and($schema['properties']['name'])->toBe(['type' => 'string'])
        ->and($schema['properties'])->toHaveKey('age')
        ->and($schema['properties']['age'])->toBe(['type' => 'integer']);
});

it('sets properties with Schema instances', function (): void {
    // Arrange
    $nameSchema = new Schema(['type' => 'string', 'minLength' => 1]);
    $ageSchema = new Schema(['type' => 'integer', 'minimum' => 0]);

    // Act
    $schema = SchemaBuilder::create()
        ->properties([
            'name' => $nameSchema,
            'age' => $ageSchema,
        ])
        ->toArray();

    // Assert
    expect($schema['properties']['name'])->toBe(['type' => 'string', 'minLength' => 1])
        ->and($schema['properties']['age'])->toBe(['type' => 'integer', 'minimum' => 0]);
});

it('sets properties with mixed array and Schema instances', function (): void {
    // Arrange
    $emailSchema = new Schema(['type' => 'string', 'format' => 'email']);

    // Act
    $schema = SchemaBuilder::create()
        ->properties([
            'name' => ['type' => 'string'],
            'email' => $emailSchema,
        ])
        ->toArray();

    // Assert
    expect($schema['properties']['name'])->toBe(['type' => 'string'])
        ->and($schema['properties']['email'])->toBe(['type' => 'string', 'format' => 'email']);
});

it('sets required properties', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->required(['name', 'email'])
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('required')
        ->and($schema['required'])->toBe(['name', 'email']);
});

it('sets required with single property', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->required(['id'])
        ->toArray();

    // Assert
    expect($schema['required'])->toBe(['id']);
});

it('sets additionalProperties to false', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->additionalProperties(false)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('additionalProperties')
        ->and($schema['additionalProperties'])->toBe(false);
});

it('sets additionalProperties to true', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->additionalProperties(true)
        ->toArray();

    // Assert
    expect($schema['additionalProperties'])->toBe(true);
});

it('sets additionalProperties with schema', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->additionalProperties(['type' => 'string'])
        ->toArray();

    // Assert
    expect($schema['additionalProperties'])->toBe(['type' => 'string']);
});

it('sets minProperties', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minProperties(1)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('minProperties')
        ->and($schema['minProperties'])->toBe(1);
});

it('sets minProperties to zero', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minProperties(0)
        ->toArray();

    // Assert
    expect($schema['minProperties'])->toBe(0);
});

it('sets maxProperties', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->maxProperties(10)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('maxProperties')
        ->and($schema['maxProperties'])->toBe(10);
});

it('sets combined object property constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minProperties(1)
        ->maxProperties(5)
        ->toArray();

    // Assert
    expect($schema['minProperties'])->toBe(1)
        ->and($schema['maxProperties'])->toBe(5);
});

// Array Constraints
it('sets items with array schema', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->items(['type' => 'string'])
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('items')
        ->and($schema['items'])->toBe(['type' => 'string']);
});

it('sets items with Schema instance', function (): void {
    // Arrange
    $itemSchema = new Schema(['type' => 'integer', 'minimum' => 0]);

    // Act
    $schema = SchemaBuilder::create()
        ->items($itemSchema)
        ->toArray();

    // Assert
    expect($schema['items'])->toBe(['type' => 'integer', 'minimum' => 0]);
});

it('sets items with complex nested schema', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->items([
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ],
        ])
        ->toArray();

    // Assert
    expect($schema['items']['type'])->toBe('object')
        ->and($schema['items']['properties'])->toHaveKey('id')
        ->and($schema['items']['properties'])->toHaveKey('name');
});

it('sets minItems', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minItems(1)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('minItems')
        ->and($schema['minItems'])->toBe(1);
});

it('sets minItems to zero', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minItems(0)
        ->toArray();

    // Assert
    expect($schema['minItems'])->toBe(0);
});

it('sets maxItems', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->maxItems(100)
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('maxItems')
        ->and($schema['maxItems'])->toBe(100);
});

it('sets combined array length constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minItems(1)
        ->maxItems(10)
        ->toArray();

    // Assert
    expect($schema['minItems'])->toBe(1)
        ->and($schema['maxItems'])->toBe(10);
});

it('sets uniqueItems to true by default', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->uniqueItems()
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('uniqueItems')
        ->and($schema['uniqueItems'])->toBe(true);
});

it('sets uniqueItems to explicit true', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->uniqueItems(true)
        ->toArray();

    // Assert
    expect($schema['uniqueItems'])->toBe(true);
});

it('sets uniqueItems to false', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->uniqueItems(false)
        ->toArray();

    // Assert
    expect($schema['uniqueItems'])->toBe(false);
});

// References
it('sets schema reference', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->ref('#/definitions/User')
        ->toArray();

    // Assert
    expect($schema)->toHaveKey('$ref')
        ->and($schema['$ref'])->toBe('#/definitions/User');
});

it('sets external schema reference', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->ref('https://example.com/schemas/user.json')
        ->toArray();

    // Assert
    expect($schema['$ref'])->toBe('https://example.com/schemas/user.json');
});

it('sets relative schema reference', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->ref('./common/address.json')
        ->toArray();

    // Assert
    expect($schema['$ref'])->toBe('./common/address.json');
});

// Build Method
it('builds Schema instance', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::String)
        ->minLength(5)
        ->build();

    // Assert
    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->toArray())->toHaveKey('type')
        ->and($schema->toArray()['type'])->toBe('string')
        ->and($schema->toArray()['minLength'])->toBe(5);
});

it('builds empty Schema when no methods called', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()->build();

    // Assert
    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->toArray())->toBe([]);
});

it('builds complex Schema with multiple constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->draft(Draft::Draft07)
        ->type(SchemaType::Object)
        ->title('User Schema')
        ->description('Schema for user validation')
        ->properties([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string', 'minLength' => 1],
        ])
        ->required(['id', 'name'])
        ->additionalProperties(false)
        ->build();

    // Assert
    expect($schema->toArray())->toHaveKey('$schema')
        ->and($schema->toArray())->toHaveKey('type')
        ->and($schema->toArray())->toHaveKey('title')
        ->and($schema->toArray())->toHaveKey('description')
        ->and($schema->toArray())->toHaveKey('properties')
        ->and($schema->toArray())->toHaveKey('required')
        ->and($schema->toArray())->toHaveKey('additionalProperties');
});

// Method Chaining
it('supports full method chaining', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->draft(Draft::Draft07)
        ->type(SchemaType::String)
        ->title('Name')
        ->description('User full name')
        ->minLength(1)
        ->maxLength(100)
        ->pattern('^[A-Za-z ]+$')
        ->toArray();

    // Assert
    expect($schema['$schema'])->toBe('http://json-schema.org/draft-07/schema#')
        ->and($schema['type'])->toBe('string')
        ->and($schema['title'])->toBe('Name')
        ->and($schema['description'])->toBe('User full name')
        ->and($schema['minLength'])->toBe(1)
        ->and($schema['maxLength'])->toBe(100)
        ->and($schema['pattern'])->toBe('^[A-Za-z ]+$');
});

it('chains numeric constraint methods', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::Number)
        ->minimum(0)
        ->maximum(100)
        ->multipleOf(0.1)
        ->toArray();

    // Assert
    expect($schema['type'])->toBe('number')
        ->and($schema['minimum'])->toBe(0)
        ->and($schema['maximum'])->toBe(100)
        ->and($schema['multipleOf'])->toBe(0.1);
});

it('chains object constraint methods', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::Object)
        ->properties(['id' => ['type' => 'integer']])
        ->required(['id'])
        ->additionalProperties(false)
        ->minProperties(1)
        ->maxProperties(10)
        ->toArray();

    // Assert
    expect($schema['type'])->toBe('object')
        ->and($schema)->toHaveKey('properties')
        ->and($schema)->toHaveKey('required')
        ->and($schema['additionalProperties'])->toBe(false)
        ->and($schema['minProperties'])->toBe(1)
        ->and($schema['maxProperties'])->toBe(10);
});

it('chains array constraint methods', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::Array)
        ->items(['type' => 'string'])
        ->minItems(1)
        ->maxItems(5)
        ->uniqueItems(true)
        ->toArray();

    // Assert
    expect($schema['type'])->toBe('array')
        ->and($schema)->toHaveKey('items')
        ->and($schema['minItems'])->toBe(1)
        ->and($schema['maxItems'])->toBe(5)
        ->and($schema['uniqueItems'])->toBe(true);
});

// Complex Real-World Schemas
it('builds user registration schema', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->draft(Draft::Draft07)
        ->type(SchemaType::Object)
        ->title('User Registration')
        ->description('Schema for user registration form')
        ->properties([
            'username' => [
                'type' => 'string',
                'minLength' => 3,
                'maxLength' => 20,
                'pattern' => '^[a-zA-Z0-9_]+$',
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
        ])
        ->required(['username', 'email'])
        ->additionalProperties(false)
        ->build();

    // Assert
    expect($schema->toArray()['properties'])->toHaveKeys(['username', 'email', 'age'])
        ->and($schema->toArray()['required'])->toBe(['username', 'email'])
        ->and($schema->toArray()['additionalProperties'])->toBe(false);
});

it('builds product schema with nested properties', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::Object)
        ->properties([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'price' => [
                'type' => 'number',
                'minimum' => 0,
                'multipleOf' => 0.01,
            ],
            'tags' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'uniqueItems' => true,
            ],
        ])
        ->required(['id', 'name', 'price'])
        ->build();

    // Assert
    expect($schema->toArray()['properties'])->toHaveKeys(['id', 'name', 'price', 'tags'])
        ->and($schema->toArray()['properties']['price']['minimum'])->toBe(0)
        ->and($schema->toArray()['properties']['tags']['type'])->toBe('array');
});

it('builds API response schema', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::Object)
        ->properties([
            'status' => [
                'type' => 'string',
                'enum' => ['success', 'error'],
            ],
            'data' => [
                'type' => 'object',
            ],
            'errors' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ])
        ->required(['status'])
        ->build();

    // Assert
    expect($schema->toArray()['properties']['status']['enum'])->toBe(['success', 'error'])
        ->and($schema->toArray()['required'])->toBe(['status']);
});

// Edge Cases
it('handles empty properties object', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->properties([])
        ->toArray();

    // Assert
    expect($schema['properties'])->toBe([]);
});

it('handles empty required array', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->required([])
        ->toArray();

    // Assert
    expect($schema['required'])->toBe([]);
});

it('handles empty enum array', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->enum([])
        ->toArray();

    // Assert
    expect($schema['enum'])->toBe([]);
});

it('handles empty types array', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->types([])
        ->toArray();

    // Assert
    expect($schema['type'])->toBe([]);
});

it('handles zero values for numeric constraints', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(0)
        ->maximum(0)
        ->exclusiveMinimum(0)
        ->exclusiveMaximum(0)
        ->toArray();

    // Assert
    expect($schema['minimum'])->toBe(0)
        ->and($schema['maximum'])->toBe(0)
        ->and($schema['exclusiveMinimum'])->toBe(0)
        ->and($schema['exclusiveMaximum'])->toBe(0);
});

it('handles negative numeric values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(-100)
        ->maximum(-10)
        ->multipleOf(-5)
        ->toArray();

    // Assert
    expect($schema['minimum'])->toBe(-100)
        ->and($schema['maximum'])->toBe(-10)
        ->and($schema['multipleOf'])->toBe(-5);
});

it('handles very large numeric values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->minimum(PHP_INT_MAX - 1_000)
        ->maximum(PHP_INT_MAX)
        ->toArray();

    // Assert
    expect($schema['minimum'])->toBe(PHP_INT_MAX - 1_000)
        ->and($schema['maximum'])->toBe(PHP_INT_MAX);
});

it('handles very small float values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->multipleOf(0.000_1)
        ->minimum(0.000_1)
        ->toArray();

    // Assert
    expect($schema['multipleOf'])->toBe(0.000_1)
        ->and($schema['minimum'])->toBe(0.000_1);
});

it('handles empty string values', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->title('')
        ->description('')
        ->pattern('')
        ->toArray();

    // Assert
    expect($schema['title'])->toBe('')
        ->and($schema['description'])->toBe('')
        ->and($schema['pattern'])->toBe('');
});

it('handles unicode strings', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->title('用户模式')
        ->description('用于用户数据验证的模式')
        ->toArray();

    // Assert
    expect($schema['title'])->toBe('用户模式')
        ->and($schema['description'])->toBe('用于用户数据验证的模式');
});

it('handles special characters in strings', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->title('User\'s "Schema"')
        ->pattern('\\d+\\.\\d{2}')
        ->toArray();

    // Assert
    expect($schema['title'])->toBe('User\'s "Schema"')
        ->and($schema['pattern'])->toBe('\\d+\\.\\d{2}');
});

it('overwrites previous value when method called multiple times', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::String)
        ->type(SchemaType::Number)
        ->minLength(5)
        ->minLength(10)
        ->toArray();

    // Assert
    expect($schema['type'])->toBe('number')
        ->and($schema['minLength'])->toBe(10);
});

it('allows mixing type and types methods', function (): void {
    // Arrange & Act
    $schema = SchemaBuilder::create()
        ->type(SchemaType::String)
        ->types([SchemaType::Number, SchemaType::Null])
        ->toArray();

    // Assert
    expect($schema['type'])->toBe(['number', 'null']);
});

it('returns same builder instance for chaining', function (): void {
    // Arrange
    $builder = SchemaBuilder::create();

    // Act
    $result1 = $builder->type(SchemaType::String);
    $result2 = $builder->minLength(5);
    $result3 = $builder->maxLength(10);

    // Assert
    expect($result1)->toBe($builder)
        ->and($result2)->toBe($builder)
        ->and($result3)->toBe($builder);
});

it('toArray returns current schema state without building', function (): void {
    // Arrange
    $builder = SchemaBuilder::create()
        ->type(SchemaType::String)
        ->minLength(5);

    // Act
    $array1 = $builder->toArray();
    $builder->maxLength(10);
    $array2 = $builder->toArray();

    // Assert
    expect($array1)->toBe(['type' => 'string', 'minLength' => 5])
        ->and($array2)->toBe(['type' => 'string', 'minLength' => 5, 'maxLength' => 10]);
});

it('build creates immutable Schema instance', function (): void {
    // Arrange
    $builder = SchemaBuilder::create()->type(SchemaType::String);

    // Act
    $schema = $builder->build();
    $builder->minLength(5);

    // Assert
    expect($schema->toArray())->toBe(['type' => 'string'])
        ->and($builder->toArray())->toBe(['type' => 'string', 'minLength' => 5]);
});
