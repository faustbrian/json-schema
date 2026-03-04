<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\DateFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new DateFormatValidator();
    expect($validator->format())->toBe('date');
});

it('validates standard RFC 3339 dates', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('1963-06-19'))->toBeTrue()
        ->and($validator->validate('2024-01-15'))->toBeTrue()
        ->and($validator->validate('2024-12-31'))->toBeTrue()
        ->and($validator->validate('2024-01-01'))->toBeTrue();
});

it('validates February 29 in leap years', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-02-29'))->toBeTrue()
        ->and($validator->validate('2020-02-29'))->toBeTrue()
        ->and($validator->validate('2000-02-29'))->toBeTrue();
});

it('validates days in months with 31 days', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-01-31'))->toBeTrue()
        ->and($validator->validate('2024-03-31'))->toBeTrue()
        ->and($validator->validate('2024-05-31'))->toBeTrue()
        ->and($validator->validate('2024-07-31'))->toBeTrue()
        ->and($validator->validate('2024-08-31'))->toBeTrue()
        ->and($validator->validate('2024-10-31'))->toBeTrue()
        ->and($validator->validate('2024-12-31'))->toBeTrue();
});

it('validates days in months with 30 days', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-04-30'))->toBeTrue()
        ->and($validator->validate('2024-06-30'))->toBeTrue()
        ->and($validator->validate('2024-09-30'))->toBeTrue()
        ->and($validator->validate('2024-11-30'))->toBeTrue();
});

it('rejects invalid month values', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-00-15'))->toBeFalse()
        ->and($validator->validate('2024-13-15'))->toBeFalse()
        ->and($validator->validate('2024-99-15'))->toBeFalse();
});

it('rejects invalid day values', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-01-00'))->toBeFalse()
        ->and($validator->validate('2024-01-32'))->toBeFalse()
        ->and($validator->validate('2024-02-30'))->toBeFalse();
});

it('rejects February 29 in non-leap years', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2023-02-29'))->toBeFalse()
        ->and($validator->validate('2021-02-29'))->toBeFalse()
        ->and($validator->validate('1900-02-29'))->toBeFalse();
});

it('rejects day 31 in months with only 30 days', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-04-31'))->toBeFalse()
        ->and($validator->validate('2024-06-31'))->toBeFalse()
        ->and($validator->validate('2024-09-31'))->toBeFalse()
        ->and($validator->validate('2024-11-31'))->toBeFalse();
});

it('rejects dates with time components', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15 14:30:00'))->toBeFalse();
});

it('rejects dates without padding', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('1998-1-20'))->toBeFalse()
        ->and($validator->validate('1998-01-1'))->toBeFalse()
        ->and($validator->validate('998-01-20'))->toBeFalse();
});

it('rejects ISO 8601 ordinal dates', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2013-350'))->toBeFalse()
        ->and($validator->validate('2024-001'))->toBeFalse();
});

it('rejects ISO 8601 week dates', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2023-W01'))->toBeFalse()
        ->and($validator->validate('2023-W13-2'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['2024-01-15']))->toBeFalse();
});

it('validates year 2000 as leap year', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('2000-02-29'))->toBeTrue();
});

it('rejects year 1900 February 29 as non-leap year', function (): void {
    $validator = new DateFormatValidator();

    expect($validator->validate('1900-02-29'))->toBeFalse();
});
