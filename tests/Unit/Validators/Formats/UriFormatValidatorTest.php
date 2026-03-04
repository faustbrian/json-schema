<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\UriFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new UriFormatValidator();
    expect($validator->format())->toBe('uri');
});

it('validates standard HTTP URIs', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com'))->toBeTrue()
        ->and($validator->validate('https://example.com'))->toBeTrue()
        ->and($validator->validate('http://www.example.com'))->toBeTrue();
});

it('validates URIs with paths', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://example.com/path'))->toBeTrue()
        ->and($validator->validate('https://example.com/path/to/resource'))->toBeTrue()
        ->and($validator->validate('http://example.com/api/v1/users'))->toBeTrue();
});

it('validates URIs with query strings', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://example.com?query=value'))->toBeTrue()
        ->and($validator->validate('https://example.com/path?foo=bar&baz=qux'))->toBeTrue()
        ->and($validator->validate('http://example.com?page=1&limit=10'))->toBeTrue();
});

it('validates URIs with fragments', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://example.com#section'))->toBeTrue()
        ->and($validator->validate('https://example.com/page#top'))->toBeTrue()
        ->and($validator->validate('http://example.com/docs#introduction'))->toBeTrue();
});

it('validates URIs with ports', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com:8080'))->toBeTrue()
        ->and($validator->validate('https://example.com:443'))->toBeTrue()
        ->and($validator->validate('http://localhost:3000'))->toBeTrue();
});

it('validates URIs with IPv4 addresses', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://192.168.1.1'))->toBeTrue()
        ->and($validator->validate('https://127.0.0.1:8080'))->toBeTrue()
        ->and($validator->validate('http://10.0.0.1/path'))->toBeTrue();
});

it('validates URIs with IPv6 addresses', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://[2001:db8::1]'))->toBeTrue()
        ->and($validator->validate('https://[::1]'))->toBeTrue()
        ->and($validator->validate('http://[2001:db8::1]:8080'))->toBeTrue();
});

it('validates URIs with different schemes', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('ftp://ftp.example.com'))->toBeTrue()
        ->and($validator->validate('mailto:user@example.com'))->toBeTrue()
        ->and($validator->validate('tel:+1-555-1234'))->toBeTrue()
        ->and($validator->validate('file:///path/to/file'))->toBeTrue();
});

it('validates URIs with percent-encoded characters', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://example.com/path%20with%20spaces'))->toBeTrue()
        ->and($validator->validate('https://example.com/%E2%9C%93'))->toBeTrue()
        ->and($validator->validate('http://example.com?name=John%20Doe'))->toBeTrue();
});

it('validates URIs with authentication', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://user:pass@example.com'))->toBeTrue()
        ->and($validator->validate('ftp://username@ftp.example.com'))->toBeTrue();
});

it('validates URIs with hyphens in scheme', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('my-scheme://example.com'))->toBeTrue();
});

it('validates URIs with plus in scheme', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('data+json://example'))->toBeTrue();
});

it('validates URIs with dots in scheme', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('vnd.example://test'))->toBeTrue();
});

it('rejects URIs without scheme', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('//example.com'))->toBeFalse()
        ->and($validator->validate('example.com'))->toBeFalse()
        ->and($validator->validate('/path/to/resource'))->toBeFalse();
});

it('rejects URIs with unescaped special characters', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://ex ample.com'))->toBeFalse()
        ->and($validator->validate('http://example.com/path with spaces'))->toBeFalse()
        ->and($validator->validate('http://example.com/<invalid>'))->toBeFalse()
        ->and($validator->validate('http://example.com/"quotes"'))->toBeFalse();
});

it('rejects URIs with unbalanced brackets', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://[2001:db8::1'))->toBeFalse()
        ->and($validator->validate('http://2001:db8::1]'))->toBeFalse();
});

it('accepts URIs with balanced brackets even if nested', function (): void {
    $validator = new UriFormatValidator();

    // Nested brackets are balanced (2 open, 2 close) - validator only checks balance
    expect($validator->validate('http://[[example.com]]'))->toBeTrue();
});

it('rejects URIs with unescaped non-ASCII characters', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://exämple.com'))->toBeFalse()
        ->and($validator->validate('http://例え.jp'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['http://example.com']))->toBeFalse()
        ->and($validator->validate((object) ['uri' => 'http://example.com']))->toBeFalse();
});

it('rejects scheme starting with digit', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('123scheme://example.com'))->toBeFalse();
});

it('validates complex URIs with all components', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('https://user:pass@example.com:8080/path/to/resource?query=value&foo=bar#section'))->toBeTrue();
});

it('validates URN scheme URIs', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('urn:isbn:0451450523'))->toBeTrue()
        ->and($validator->validate('urn:ietf:rfc:3986'))->toBeTrue();
});

it('rejects URIs with pipe character', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com/path|file'))->toBeFalse();
});

it('rejects URIs with caret character', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com/path^file'))->toBeFalse();
});

it('rejects URIs with backtick character', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com/path`file'))->toBeFalse();
});

it('rejects URIs with curly braces', function (): void {
    $validator = new UriFormatValidator();

    expect($validator->validate('http://example.com/path{file}'))->toBeFalse();
});
