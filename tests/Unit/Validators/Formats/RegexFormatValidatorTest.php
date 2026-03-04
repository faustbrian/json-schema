<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\RegexFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new RegexFormatValidator();
    expect($validator->format())->toBe('regex');
});

it('validates simple regex patterns', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('^test$'))->toBeTrue()
        ->and($validator->validate('[a-z]+'))->toBeTrue()
        ->and($validator->validate('\\d{3}'))->toBeTrue();
});

it('validates regex with groups', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('(abc|def)'))->toBeTrue()
        ->and($validator->validate('(a)(b)(c)'))->toBeTrue()
        ->and($validator->validate('(?:test)'))->toBeTrue();
});

it('validates regex with character classes', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('[a-zA-Z0-9]'))->toBeTrue()
        ->and($validator->validate('[^0-9]'))->toBeTrue()
        ->and($validator->validate('[\\w\\s]'))->toBeTrue();
});

it('validates regex with quantifiers', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('a*'))->toBeTrue()
        ->and($validator->validate('a+'))->toBeTrue()
        ->and($validator->validate('a?'))->toBeTrue()
        ->and($validator->validate('a{2,5}'))->toBeTrue();
});

it('validates regex with escape sequences', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('\\d+'))->toBeTrue()
        ->and($validator->validate('\\w+'))->toBeTrue()
        ->and($validator->validate('\\s*'))->toBeTrue();
});

it('rejects regex with unclosed groups', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('^(abc]'))->toBeFalse()
        ->and($validator->validate('(unclosed'))->toBeFalse();
});

it('rejects regex with unclosed brackets', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate('[a-z'))->toBeFalse()
        ->and($validator->validate('[unclosed'))->toBeFalse();
});

it('validates empty regex pattern', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate(''))->toBeTrue();
});

it('rejects non-string values', function (): void {
    $validator = new RegexFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['^test$']))->toBeFalse();
});
