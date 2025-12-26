<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\RelativeJsonPointerFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();
    expect($validator->format())->toBe('relative-json-pointer');
});

it('validates relative JSON pointer with just a number', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('0'))->toBeTrue()
        ->and($validator->validate('1'))->toBeTrue()
        ->and($validator->validate('2'))->toBeTrue()
        ->and($validator->validate('10'))->toBeTrue();
});

it('validates relative JSON pointer with number and path', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('0/foo/bar'))->toBeTrue()
        ->and($validator->validate('1/foo'))->toBeTrue()
        ->and($validator->validate('2/0'))->toBeTrue()
        ->and($validator->validate('10/items/0'))->toBeTrue();
});

it('validates relative JSON pointer with number and hash', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('0#'))->toBeTrue()
        ->and($validator->validate('1#'))->toBeTrue()
        ->and($validator->validate('10#'))->toBeTrue();
});

it('rejects regular JSON pointer without number prefix', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('/foo/bar'))->toBeFalse()
        ->and($validator->validate('/items/0'))->toBeFalse();
});

it('rejects negative number prefix', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('-1/foo/bar'))->toBeFalse()
        ->and($validator->validate('-5'))->toBeFalse();
});

it('rejects explicit positive sign', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('+1/foo/bar'))->toBeFalse()
        ->and($validator->validate('+5'))->toBeFalse();
});

it('rejects number with leading zeros', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('01/a'))->toBeFalse()
        ->and($validator->validate('01#'))->toBeFalse()
        ->and($validator->validate('001'))->toBeFalse();
});

it('rejects double hash', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate('0##'))->toBeFalse()
        ->and($validator->validate('1##'))->toBeFalse();
});

it('rejects empty string', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new RelativeJsonPointerFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['0/foo']))->toBeFalse();
});
