<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\UriTemplateFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new UriTemplateFormatValidator();
    expect($validator->format())->toBe('uri-template');
});

it('validates simple URI templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('https://example.com/users/{id}'))->toBeTrue()
        ->and($validator->validate('/api/{version}/users'))->toBeTrue()
        ->and($validator->validate('/{resource}'))->toBeTrue();
});

it('validates URI templates with multiple variables', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/api/{version}/users/{userId}'))->toBeTrue()
        ->and($validator->validate('/{category}/{subcategory}/{item}'))->toBeTrue()
        ->and($validator->validate('https://example.com/{path}/{file}'))->toBeTrue();
});

it('validates URI templates without variables', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('https://example.com/static/path'))->toBeTrue()
        ->and($validator->validate('/api/users'))->toBeTrue();
});

it('validates URI templates with query expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/search{?query}'))->toBeTrue()
        ->and($validator->validate('/search{?query,page}'))->toBeTrue()
        ->and($validator->validate('/api/users{?filter,sort,limit}'))->toBeTrue();
});

it('validates URI templates with fragment expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/page{#section}'))->toBeTrue()
        ->and($validator->validate('/docs{#fragment}'))->toBeTrue();
});

it('validates URI templates with path expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/base{/path}'))->toBeTrue()
        ->and($validator->validate('/files{/path,file}'))->toBeTrue();
});

it('validates URI templates with reserved expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/proxy{+url}'))->toBeTrue()
        ->and($validator->validate('/redirect{+target}'))->toBeTrue();
});

it('validates URI templates with dot expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/file{.ext}'))->toBeTrue()
        ->and($validator->validate('/resource{.format}'))->toBeTrue();
});

it('validates URI templates with semicolon expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path{;params}'))->toBeTrue()
        ->and($validator->validate('/resource{;x,y}'))->toBeTrue();
});

it('validates URI templates with ampersand expansion operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/search?fixed=value{&optional}'))->toBeTrue();
});

it('validates URI templates with variable names containing underscores', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/users/{user_id}'))->toBeTrue()
        ->and($validator->validate('/{first_name}_{last_name}'))->toBeTrue();
});

it('validates URI templates with variable names containing dots', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/api/{api.version}'))->toBeTrue()
        ->and($validator->validate('/{user.id}'))->toBeTrue();
});

it('validates URI templates with variable names containing numbers', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/api/v{version2}'))->toBeTrue()
        ->and($validator->validate('/{item123}'))->toBeTrue();
});

it('validates URI templates with explode modifier', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path{?params*}'))->toBeTrue()
        ->and($validator->validate('/list{/items*}'))->toBeTrue();
});

it('validates URI templates with multiple operators', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/api/{version}/users{?filter,sort}{#section}'))->toBeTrue();
});

it('validates URI templates with comma-separated variables', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/search{?q,page,limit}'))->toBeTrue()
        ->and($validator->validate('/{x,y,z}'))->toBeTrue();
});

it('rejects URI templates with empty expressions', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path/{}'))->toBeFalse()
        ->and($validator->validate('/api/{}/users'))->toBeFalse();
});

it('rejects URI templates with unbalanced braces', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path/{variable'))->toBeFalse()
        ->and($validator->validate('/path/variable}'))->toBeFalse()
        ->and($validator->validate('/path/{var{inner}}'))->toBeFalse();
});

it('rejects URI templates with invalid variable names', function (): void {
    $validator = new UriTemplateFormatValidator();

    // Hyphens are not allowed in variable names per RFC 6570
    expect($validator->validate('/path/{invalid-variable}'))->toBeFalse();
});

it('validates URI templates with @ operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    // @ is a valid operator in URI templates
    expect($validator->validate('/path/{@invalid}'))->toBeTrue();
});

it('rejects URI templates with unescaped special characters outside templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path with spaces/{id}'))->toBeFalse()
        ->and($validator->validate('/path<invalid>/{id}'))->toBeFalse()
        ->and($validator->validate('/path"quote"/{id}'))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['/path/{id}']))->toBeFalse()
        ->and($validator->validate((object) ['template' => '/path/{id}']))->toBeFalse();
});

it('validates URI templates with equals operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path{=var}'))->toBeTrue();
});

it('validates URI templates with exclamation operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path{!var}'))->toBeTrue();
});

it('validates URI templates with pipe operator', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path{|var}'))->toBeTrue();
});

it('validates complex real-world URI templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('https://api.github.com/repos/{owner}/{repo}/issues{?state,labels,sort}'))->toBeTrue()
        ->and($validator->validate('http://example.com/dictionary/{term:1}/{term}'))->toBeTrue();
});

it('validates URI templates with colon in variable names', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path/{var:name}'))->toBeTrue()
        ->and($validator->validate('/item/{id:123}'))->toBeTrue();
});

it('rejects URI templates with backslash outside templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path\\file/{id}'))->toBeFalse();
});

it('rejects URI templates with caret outside templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path^file/{id}'))->toBeFalse();
});

it('rejects URI templates with backtick outside templates', function (): void {
    $validator = new UriTemplateFormatValidator();

    expect($validator->validate('/path`file/{id}'))->toBeFalse();
});
