<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\IriReferenceFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new IriReferenceFormatValidator();
    expect($validator->format())->toBe('iri-reference');
});

it('validates absolute IRIs', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('https://example.com/path'))->toBeTrue()
        ->and($validator->validate('http://example.com'))->toBeTrue()
        ->and($validator->validate('ftp://ftp.example.com'))->toBeTrue();
});

it('validates internationalized IRIs', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('https://日本.jp/パス'))->toBeTrue()
        ->and($validator->validate('http://münchen.de'))->toBeTrue();
});

it('validates relative references', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('/path/to/resource'))->toBeTrue()
        ->and($validator->validate('../relative/path'))->toBeTrue()
        ->and($validator->validate('./current/path'))->toBeTrue()
        ->and($validator->validate('resource'))->toBeTrue();
});

it('validates IRI references with query strings', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('https://example.com/path?key=value'))->toBeTrue()
        ->and($validator->validate('/path?key=value'))->toBeTrue()
        ->and($validator->validate('?key=value'))->toBeTrue();
});

it('validates IRI references with fragments', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('https://example.com/page#section'))->toBeTrue()
        ->and($validator->validate('/page#section'))->toBeTrue()
        ->and($validator->validate('#section'))->toBeTrue();
});

it('validates empty string as valid IRI reference', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate(''))->toBeTrue();
});

it('rejects non-string values', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['https://example.com']))->toBeFalse();
});

it('validates scheme-relative references', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('//example.com/path'))->toBeTrue()
        ->and($validator->validate('//cdn.example.com/script.js'))->toBeTrue();
});

it('rejects IRI references with invalid characters', function (): void {
    $validator = new IriReferenceFormatValidator();

    // Invalid characters: < > " { } | \ ^ ` and space
    expect($validator->validate('https://example.com/<path>'))->toBeFalse()
        ->and($validator->validate('https://example.com/>path'))->toBeFalse()
        ->and($validator->validate('https://example.com/"path"'))->toBeFalse()
        ->and($validator->validate('https://example.com/{path}'))->toBeFalse()
        ->and($validator->validate('https://example.com/|path'))->toBeFalse()
        ->and($validator->validate('https://example.com/\\path'))->toBeFalse()
        ->and($validator->validate('https://example.com/^path'))->toBeFalse()
        ->and($validator->validate('https://example.com/`path'))->toBeFalse()
        ->and($validator->validate('https://example.com/ space'))->toBeFalse();
});

it('rejects IRI references with unbalanced brackets', function (): void {
    $validator = new IriReferenceFormatValidator();

    expect($validator->validate('https://[example.com/path'))->toBeFalse()
        ->and($validator->validate('https://example].com/path'))->toBeFalse()
        ->and($validator->validate('https://[[example.com]'))->toBeFalse()
        ->and($validator->validate('[path/to/resource'))->toBeFalse();
});
