<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\IriFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new IriFormatValidator();
    expect($validator->format())->toBe('iri');
});

it('validates ASCII IRIs', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('https://example.com/path'))->toBeTrue()
        ->and($validator->validate('http://example.com'))->toBeTrue()
        ->and($validator->validate('ftp://ftp.example.com'))->toBeTrue();
});

it('validates internationalized IRIs', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('https://日本.jp/パス'))->toBeTrue()
        ->and($validator->validate('http://münchen.de'))->toBeTrue();
});

it('validates IRIs with query strings', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('https://example.com/path?key=value'))->toBeTrue()
        ->and($validator->validate('https://example.com?foo=bar&baz=qux'))->toBeTrue();
});

it('validates IRIs with fragments', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('https://example.com/page#section'))->toBeTrue()
        ->and($validator->validate('https://example.com#top'))->toBeTrue();
});

it('rejects empty strings', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['https://example.com']))->toBeFalse();
});

it('rejects IRIs without scheme', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('//example.com/path'))->toBeFalse()
        ->and($validator->validate('example.com/path'))->toBeFalse();
});

it('rejects relative references', function (): void {
    $validator = new IriFormatValidator();

    expect($validator->validate('/path/to/resource'))->toBeFalse()
        ->and($validator->validate('../relative/path'))->toBeFalse();
});

it('rejects IRIs with invalid characters', function (): void {
    $validator = new IriFormatValidator();

    // Line 80: Invalid character detection loop
    expect($validator->validate('https://example.com/<invalid>'))->toBeFalse()
        ->and($validator->validate('https://example.com/>invalid'))->toBeFalse()
        ->and($validator->validate('https://example.com/"quoted"'))->toBeFalse()
        ->and($validator->validate('https://example.com/{bracket}'))->toBeFalse()
        ->and($validator->validate('https://example.com/}bracket'))->toBeFalse()
        ->and($validator->validate('https://example.com/pipe|here'))->toBeFalse()
        ->and($validator->validate('https://example.com/back\\slash'))->toBeFalse()
        ->and($validator->validate('https://example.com/caret^here'))->toBeFalse()
        ->and($validator->validate('https://example.com/back`tick'))->toBeFalse()
        ->and($validator->validate('https://example.com/with space'))->toBeFalse();
});

it('rejects IRIs with unbalanced brackets', function (): void {
    $validator = new IriFormatValidator();

    // Line 89: Unbalanced brackets check
    expect($validator->validate('https://example.com/path[unclosed'))->toBeFalse()
        ->and($validator->validate('https://example.com/path]unopened'))->toBeFalse()
        ->and($validator->validate('https://[::1/path'))->toBeFalse()
        ->and($validator->validate('https://::1]/path'))->toBeFalse()
        ->and($validator->validate('http://[[[example.com]'))->toBeFalse();
});

it('rejects unescaped IPv6 addresses without brackets', function (): void {
    $validator = new IriFormatValidator();

    // Line 102: Unescaped IPv6 colon check - host with multiple colons not in brackets
    expect($validator->validate('http://2001:db8::1/path'))->toBeFalse()
        ->and($validator->validate('https://fe80::1/resource'))->toBeFalse()
        ->and($validator->validate('ftp://::1:8080/file'))->toBeFalse()
        ->and($validator->validate('http://2001:0db8:85a3::8a2e:0370:7334'))->toBeFalse();
});

it('rejects IRIs with scheme but no content after colon', function (): void {
    $validator = new IriFormatValidator();

    // Line 118: Empty afterScheme check
    expect($validator->validate('http:'))->toBeFalse()
        ->and($validator->validate('https:'))->toBeFalse()
        ->and($validator->validate('ftp:'))->toBeFalse()
        ->and($validator->validate('custom-scheme:'))->toBeFalse();
});

it('rejects IRIs with brackets in userinfo', function (): void {
    $validator = new IriFormatValidator();

    // Lines 124-129: Userinfo validation with brackets
    expect($validator->validate('http://user[name@example.com/path'))->toBeFalse()
        ->and($validator->validate('https://[invalid]user@example.com'))->toBeFalse()
        ->and($validator->validate('ftp://user]name@host.com'))->toBeFalse()
        ->and($validator->validate('http://test[bracket@example.com/resource'))->toBeFalse();
});

it('validates IRIs with IPv6 addresses in brackets', function (): void {
    $validator = new IriFormatValidator();

    // Valid IPv6 addresses properly enclosed in brackets
    expect($validator->validate('http://[2001:db8::1]/path'))->toBeTrue()
        ->and($validator->validate('https://[::1]/resource'))->toBeTrue()
        ->and($validator->validate('http://[fe80::1]/file'))->toBeTrue()
        ->and($validator->validate('https://[2001:0db8:85a3::8a2e:0370:7334]/page'))->toBeTrue()
        ->and($validator->validate('ftp://user:pass@[::1]:8080/file'))->toBeTrue();
});
