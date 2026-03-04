<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Concerns;

use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\Validators\Draft202012Validator;
use Cline\JsonSchema\ValueObjects\Schema;

use function expect;
use function it;

// minLength Tests

it('validates minLength with exact length', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 5]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minLength with longer string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 3]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails minLength with shorter string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 10]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates minLength with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 0]);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minLength with unicode characters', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 3]);

    $result = $validator->validate('🚀🎉💯', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates minLength counts unicode characters correctly', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 5]);

    $result = $validator->validate('café', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when minLength is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for minLength', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['minLength' => 5]);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// maxLength Tests

it('validates maxLength with exact length', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 5]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maxLength with shorter string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 10]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails maxLength with longer string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 3]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates maxLength with zero', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 0]);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates maxLength with unicode characters', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 3]);

    $result = $validator->validate('🚀🎉💯', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when maxLength is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('very long string', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for maxLength', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['maxLength' => 3]);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// pattern Tests

it('validates pattern with matching string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^[a-z]+$']);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails pattern with non-matching string', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^[a-z]+$']);

    $result = $validator->validate('Hello123', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern with partial match', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '[0-9]+']);

    $result = $validator->validate('abc123def', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with unicode characters', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '🚀']);

    $result = $validator->validate('hello 🚀 world', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with digit shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\d+']);

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('abc', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern with word shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\w+']);

    $result = $validator->validate('hello_world123', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with whitespace shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\s+']);

    $result = $validator->validate('hello world', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with unicode property', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\p{Letter}+']);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with negated unicode property', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\P{Letter}+']);

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with digit unicode property', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\p{digit}+']);

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with complex regex', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$']);

    $result = $validator->validate('test@example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('invalid-email', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('passes validation when pattern is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for pattern', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^[a-z]+$']);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// format Tests

it('validates format date-time', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'date-time',
    ]);

    $result = $validator->validate('2023-01-15T12:30:00Z', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format email', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'email',
    ]);

    $result = $validator->validate('test@example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format uri', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'uri',
    ]);

    $result = $validator->validate('https://example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format hostname', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'hostname',
    ]);

    $result = $validator->validate('example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format ipv4', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'ipv4',
    ]);

    $result = $validator->validate('192.168.1.1', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format ipv6', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'ipv6',
    ]);

    $result = $validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format uuid', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'uuid',
    ]);

    $result = $validator->validate('550e8400-e29b-41d4-a716-446655440000', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format json-pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'json-pointer',
    ]);

    $result = $validator->validate('/foo/bar', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format uri-reference', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'uri-reference',
    ]);

    $result = $validator->validate('../relative/path', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format uri-template', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'uri-template',
    ]);

    $result = $validator->validate('https://example.com/{id}', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('ignores unknown format', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'unknown-format',
    ]);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when format is not present', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for format', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'format' => 'email',
    ]);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// Combined string validations

it('validates combined minLength and maxLength', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'minLength' => 5,
        'maxLength' => 10,
    ]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('hi', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate('this is too long', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined length and pattern', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'minLength' => 5,
        'pattern' => '^[a-z]+$',
    ]);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('hi', $schema->toArray());
    expect($result->isValid())->toBeFalse();

    $result = $validator->validate('Hello', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates combined pattern and format', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema([
        'pattern' => '@example\\.com$',
        'format' => 'email',
    ]);

    $result = $validator->validate('test@example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('test@other.com', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

// Additional format validator tests for complete coverage

it('validates format date', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'date']);

    $result = $validator->validate('2023-01-15', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format time', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'time']);

    $result = $validator->validate('12:30:00', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format duration', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'duration']);

    $result = $validator->validate('P3Y6M4DT12H30M5S', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format idn-email', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'idn-email']);

    $result = $validator->validate('test@example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format idn-hostname', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'idn-hostname']);

    $result = $validator->validate('example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format iri', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'iri']);

    $result = $validator->validate('https://example.com', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format iri-reference', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'iri-reference']);

    $result = $validator->validate('../relative/path', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format regex', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'regex']);

    $result = $validator->validate('^[a-z]+$', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates format relative-json-pointer', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['format' => 'relative-json-pointer']);

    $result = $validator->validate('0/foo', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// contentEncoding Tests (Draft 07 validates these, Draft 2020-12 treats them as annotation-only)

it('validates contentEncoding with valid base64', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate('SGVsbG8gV29ybGQ=', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contentEncoding with invalid base64', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate('Not valid base64!@#', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contentEncoding with base64 padding', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate('YQ==', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentEncoding with base64 no padding needed', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate('YWJj', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentEncoding with empty base64 string', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate('', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('ignores unknown contentEncoding', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'unknown-encoding']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when contentEncoding is not present', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for contentEncoding', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// contentMediaType Tests (Draft 07 validates these, Draft 2020-12 treats them as annotation-only)

it('validates contentMediaType with valid JSON', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    $result = $validator->validate('{"key":"value"}', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contentMediaType with invalid JSON', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    $result = $validator->validate('{invalid json}', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates contentMediaType with JSON array', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    $result = $validator->validate('[1,2,3]', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentMediaType with empty JSON object', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    $result = $validator->validate('{}', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentMediaType with base64 encoded JSON', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'contentEncoding' => 'base64',
        'contentMediaType' => 'application/json',
    ]);

    // Base64 encoded: {"key":"value"}
    $result = $validator->validate('eyJrZXkiOiJ2YWx1ZSJ9', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('fails contentMediaType with base64 encoded invalid JSON', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'contentEncoding' => 'base64',
        'contentMediaType' => 'application/json',
    ]);

    // Base64 encoded: {invalid json}
    $result = $validator->validate('e2ludmFsaWQganNvbn0=', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('fails contentMediaType when base64 decoding fails', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema([
        'contentEncoding' => 'base64',
        'contentMediaType' => 'application/json',
    ]);

    $result = $validator->validate('Not valid base64!@#', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('ignores unknown contentMediaType', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'text/plain']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when contentMediaType is not present', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['type' => 'string']);

    $result = $validator->validate('anything', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('passes validation when data is not string for contentMediaType', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    $result = $validator->validate(123, $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

// Pattern translation edge cases

it('validates pattern with negated digit shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\D+']);

    $result = $validator->validate('abc', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern with negated word shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\W+']);

    $result = $validator->validate('!@#', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('abc', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern with negated whitespace shorthand', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\S+']);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('   ', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern with unicode hex escape', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\x{1F680}']);

    $result = $validator->validate('🚀', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with negated digit property', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\P{digit}+']);

    $result = $validator->validate('abc', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeFalse();
});

it('validates pattern requiring UTF-8 mode for multibyte characters', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^.{3}$']);

    // Non-ASCII pattern triggers UTF-8 mode
    $schema2 = new Schema(['pattern' => 'café']);
    $result = $validator->validate('café', $schema2->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with ECMA whitespace including unicode separators', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '\\s']);

    // Test various whitespace characters
    $result = $validator->validate("\t", $schema->toArray()); // tab
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate("\n", $schema->toArray()); // LF
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate(' ', $schema->toArray()); // space
    expect($result->isValid())->toBeTrue();
});

// Additional edge case tests to ensure complete coverage

it('validates pattern without UTF-8 mode for ASCII-only patterns', function (): void {
    $validator = new Draft202012Validator();
    $schema = new Schema(['pattern' => '^[a-z]+$']);

    $result = $validator->validate('hello', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentEncoding with base64 special characters', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentEncoding' => 'base64']);

    // Base64 with + and / characters
    $result = $validator->validate('aGVsbG8+d29ybGQ/', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentMediaType with JSON primitive values', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    // JSON allows primitive values at root level
    $result = $validator->validate('"string"', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('123', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('true', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    $result = $validator->validate('null', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates contentMediaType with whitespace-only JSON', function (): void {
    $validator = new Draft07Validator();
    $schema = new Schema(['contentMediaType' => 'application/json']);

    // JSON with leading/trailing whitespace
    $result = $validator->validate('  {"key":"value"}  ', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});

it('validates pattern with all unicode property translations', function (): void {
    $validator = new Draft202012Validator();

    // Test \p{Letter}
    $schema = new Schema(['pattern' => '\\p{Letter}']);
    $result = $validator->validate('a', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Test \P{Letter}
    $schema = new Schema(['pattern' => '\\P{Letter}']);
    $result = $validator->validate('1', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Test \p{digit}
    $schema = new Schema(['pattern' => '\\p{digit}']);
    $result = $validator->validate('5', $schema->toArray());
    expect($result->isValid())->toBeTrue();

    // Test \P{digit}
    $schema = new Schema(['pattern' => '\\P{digit}']);
    $result = $validator->validate('a', $schema->toArray());
    expect($result->isValid())->toBeTrue();
});
