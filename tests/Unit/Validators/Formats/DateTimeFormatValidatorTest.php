<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\DateTimeFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new DateTimeFormatValidator();
    expect($validator->format())->toBe('date-time');
});

it('validates standard date-time with Z timezone', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00Z'))->toBeTrue()
        ->and($validator->validate('2024-12-31T23:59:59Z'))->toBeTrue()
        ->and($validator->validate('2024-01-01T00:00:00Z'))->toBeTrue();
});

it('validates date-time with lowercase z timezone', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00z'))->toBeTrue();
});

it('validates date-time with lowercase t separator', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15t14:30:00Z'))->toBeTrue();
});

it('validates date-time with fractional seconds', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00.123Z'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00.123456Z'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00.1Z'))->toBeTrue();
});

it('validates date-time with positive timezone offset', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00+05:30'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00+00:00'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00+12:00'))->toBeTrue();
});

it('validates date-time with negative timezone offset', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00-08:00'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00-05:00'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00-00:00'))->toBeTrue();
});

it('validates date-time at midnight', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T00:00:00Z'))->toBeTrue();
});

it('validates date-time at end of day', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T23:59:59Z'))->toBeTrue();
});

it('validates leap second at end of UTC day', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-12-31T23:59:60Z'))->toBeTrue();
});

it('validates leap second with timezone offset conversion to UTC 23:59', function (): void {
    $validator = new DateTimeFormatValidator();

    // 15:59:60 PST (-08:00) = 23:59:60 UTC
    expect($validator->validate('2024-12-31T15:59:60-08:00'))->toBeTrue();
});

it('validates February 29 in leap years', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-02-29T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2020-02-29T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2000-02-29T12:00:00Z'))->toBeTrue();
});

it('validates days in months with 31 days', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-03-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-05-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-07-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-08-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-10-31T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-12-31T12:00:00Z'))->toBeTrue();
});

it('validates days in months with 30 days', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-04-30T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-06-30T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-09-30T12:00:00Z'))->toBeTrue()
        ->and($validator->validate('2024-11-30T12:00:00Z'))->toBeTrue();
});

it('rejects invalid month values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-00-15T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-13-15T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-99-15T14:30:00Z'))->toBeFalse();
});

it('rejects invalid day values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-00T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-32T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-02-30T14:30:00Z'))->toBeFalse();
});

it('rejects February 29 in non-leap years', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2023-02-29T12:00:00Z'))->toBeFalse()
        ->and($validator->validate('2021-02-29T12:00:00Z'))->toBeFalse()
        ->and($validator->validate('1900-02-29T12:00:00Z'))->toBeFalse();
});

it('rejects day 31 in months with only 30 days', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-04-31T12:00:00Z'))->toBeFalse()
        ->and($validator->validate('2024-06-31T12:00:00Z'))->toBeFalse()
        ->and($validator->validate('2024-09-31T12:00:00Z'))->toBeFalse()
        ->and($validator->validate('2024-11-31T12:00:00Z'))->toBeFalse();
});

it('rejects invalid hour values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T24:00:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T25:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T99:00:00Z'))->toBeFalse();
});

it('rejects invalid minute values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:60:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T14:99:00Z'))->toBeFalse();
});

it('rejects invalid second values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:61Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T14:30:99Z'))->toBeFalse();
});

it('rejects leap second not at 23:59 UTC', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:60Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T23:58:60Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T22:59:60Z'))->toBeFalse();
});

it('rejects invalid timezone offset hours', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00+24:00'))->toBeFalse()
        ->and($validator->validate('2024-01-15T14:30:00+25:00'))->toBeFalse();
});

it('rejects invalid timezone offset minutes', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00+05:60'))->toBeFalse()
        ->and($validator->validate('2024-01-15T14:30:00+05:99'))->toBeFalse();
});

it('rejects date-time without timezone', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00'))->toBeFalse();
});

it('rejects date-time with invalid format', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15 14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024/01/15T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('15-01-2024T14:30:00Z'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['2024-01-15T14:30:00Z']))->toBeFalse()
        ->and($validator->validate((object) ['datetime' => '2024-01-15T14:30:00Z']))->toBeFalse();
});

it('validates year 2000 as leap year', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2000-02-29T12:00:00Z'))->toBeTrue();
});

it('rejects year 1900 February 29 as non-leap year', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('1900-02-29T12:00:00Z'))->toBeFalse();
});

it('validates date-time with various timezone offsets', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00+01:00'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00+03:30'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00-11:00'))->toBeTrue();
});

it('rejects date-time with incomplete components', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T14Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15TZ'))->toBeFalse();
});

it('rejects date-time with single-digit components', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-1-15T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-5T14:30:00Z'))->toBeFalse()
        ->and($validator->validate('2024-01-15T4:30:00Z'))->toBeFalse();
});

it('validates date-time with fractional seconds and timezone offset', function (): void {
    $validator = new DateTimeFormatValidator();

    expect($validator->validate('2024-01-15T14:30:00.123+05:30'))->toBeTrue()
        ->and($validator->validate('2024-01-15T14:30:00.999999-08:00'))->toBeTrue();
});
