<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\IdnEmailFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new IdnEmailFormatValidator();
    expect($validator->format())->toBe('idn-email');
});

it('validates standard ASCII email addresses', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('user@example.com'))->toBeTrue()
        ->and($validator->validate('john.doe@example.com'))->toBeTrue()
        ->and($validator->validate('test@example.test'))->toBeTrue();
});

it('validates internationalized email addresses', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('실례@실례.테스트'))->toBeTrue()
        ->and($validator->validate('user@münchen.de'))->toBeTrue()
        ->and($validator->validate('user@例え.jp'))->toBeTrue();
});

it('rejects purely numeric strings', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('2962'))->toBeFalse()
        ->and($validator->validate('123'))->toBeFalse()
        ->and($validator->validate('0'))->toBeFalse();
});

it('rejects email addresses without @ symbol', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('userexample.com'))->toBeFalse()
        ->and($validator->validate('user.example.com'))->toBeFalse();
});

it('rejects email addresses with multiple @ symbols', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('user@@example.com'))->toBeFalse()
        ->and($validator->validate('user@test@example.com'))->toBeFalse();
});

it('rejects email addresses with empty local or domain parts', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate('@example.com'))->toBeFalse()
        ->and($validator->validate('user@'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new IdnEmailFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['email@example.com']))->toBeFalse();
});
