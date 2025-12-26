<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\UriReferenceFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new UriReferenceFormatValidator();
    expect($validator->format())->toBe('uri-reference');
});

it('validates empty string as valid URI reference', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate(''))->toBeTrue();
});

it('validates absolute URIs', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('https://example.com'))->toBeTrue()
        ->and($validator->validate('http://example.com/path'))->toBeTrue()
        ->and($validator->validate('ftp://ftp.example.com'))->toBeTrue();
});

it('validates relative paths', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('path/to/resource'))->toBeTrue()
        ->and($validator->validate('resource'))->toBeTrue()
        ->and($validator->validate('file.html'))->toBeTrue();
});

it('validates absolute paths', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path/to/resource'))->toBeTrue()
        ->and($validator->validate('/api/users'))->toBeTrue()
        ->and($validator->validate('/index.html'))->toBeTrue();
});

it('validates relative paths with dot segments', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('./resource'))->toBeTrue()
        ->and($validator->validate('../resource'))->toBeTrue()
        ->and($validator->validate('../../path/to/resource'))->toBeTrue();
});

it('validates network-path references', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('//example.com'))->toBeTrue()
        ->and($validator->validate('//example.com/path'))->toBeTrue()
        ->and($validator->validate('//user@example.com'))->toBeTrue();
});

it('validates query-only references', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('?query=value'))->toBeTrue()
        ->and($validator->validate('?foo=bar&baz=qux'))->toBeTrue()
        ->and($validator->validate('?page=1'))->toBeTrue();
});

it('validates fragment-only references', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('#section'))->toBeTrue()
        ->and($validator->validate('#top'))->toBeTrue()
        ->and($validator->validate('#'))->toBeTrue();
});

it('validates URI references with percent-encoded characters', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path%20with%20spaces'))->toBeTrue()
        ->and($validator->validate('file%20name.html'))->toBeTrue()
        ->and($validator->validate('?name=John%20Doe'))->toBeTrue();
});

it('validates URI references with IPv6 addresses', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('//[2001:db8::1]'))->toBeTrue()
        ->and($validator->validate('//[::1]'))->toBeTrue()
        ->and($validator->validate('//[2001:db8::1]/path'))->toBeTrue();
});

it('validates URI references with all components', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path/to/resource?query=value#section'))->toBeTrue()
        ->and($validator->validate('//example.com/path?foo=bar#top'))->toBeTrue();
});

it('validates relative paths with special characters', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('path/with-dash'))->toBeTrue()
        ->and($validator->validate('path/with_underscore'))->toBeTrue()
        ->and($validator->validate('path/with.dot'))->toBeTrue();
});

it('rejects URI references with unescaped special characters', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('path with spaces'))->toBeFalse()
        ->and($validator->validate('/path/with/spaces here'))->toBeFalse()
        ->and($validator->validate('<invalid>'))->toBeFalse()
        ->and($validator->validate('"quotes"'))->toBeFalse();
});

it('rejects URI references with unbalanced brackets', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('//[2001:db8::1'))->toBeFalse()
        ->and($validator->validate('//2001:db8::1]'))->toBeFalse();
});

it('accepts URI references with balanced brackets even if nested', function (): void {
    $validator = new UriReferenceFormatValidator();

    // Nested brackets are balanced (2 open, 2 close) - validator only checks balance
    expect($validator->validate('//[[example.com]]'))->toBeTrue();
});

it('rejects URI references with unescaped non-ASCII characters', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('//exämple.com'))->toBeFalse()
        ->and($validator->validate('/path/to/résumé.pdf'))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['/path']))->toBeFalse()
        ->and($validator->validate((object) ['uri' => '/path']))->toBeFalse();
});

it('rejects URI references with pipe character', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path|file'))->toBeFalse();
});

it('rejects URI references with backslash character', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path\\file'))->toBeFalse();
});

it('rejects URI references with caret character', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path^file'))->toBeFalse();
});

it('rejects URI references with backtick character', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path`file'))->toBeFalse();
});

it('rejects URI references with curly braces', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('/path/{file}'))->toBeFalse()
        ->and($validator->validate('path/}file'))->toBeFalse();
});

it('validates complex relative paths', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('../../../resource'))->toBeTrue()
        ->and($validator->validate('./path/to/../resource'))->toBeTrue();
});

it('validates URI references with multiple query parameters', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('?a=1&b=2&c=3'))->toBeTrue()
        ->and($validator->validate('/path?filter=active&sort=name&page=1'))->toBeTrue();
});

it('validates URI references with fragments containing special chars', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('#section-1'))->toBeTrue()
        ->and($validator->validate('#top_of_page'))->toBeTrue()
        ->and($validator->validate('#part.two'))->toBeTrue();
});

it('validates same-document references', function (): void {
    $validator = new UriReferenceFormatValidator();

    expect($validator->validate('#'))->toBeTrue()
        ->and($validator->validate('?'))->toBeTrue();
});
