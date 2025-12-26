<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\TimeFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new TimeFormatValidator();
    expect($validator->format())->toBe('time');
});

it('validates standard time with Z timezone', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('08:30:06Z'))->toBeTrue()
        ->and($validator->validate('23:59:59Z'))->toBeTrue()
        ->and($validator->validate('00:00:00Z'))->toBeTrue();
});

it('validates time with lowercase z timezone', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:00z'))->toBeTrue();
});

it('validates time with fractional seconds', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('08:30:06.283185Z'))->toBeTrue()
        ->and($validator->validate('14:30:00.123Z'))->toBeTrue()
        ->and($validator->validate('14:30:00.1Z'))->toBeTrue();
});

it('validates time with positive timezone offset', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('08:30:06+05:30'))->toBeTrue()
        ->and($validator->validate('14:30:00+00:00'))->toBeTrue()
        ->and($validator->validate('14:30:00+12:00'))->toBeTrue();
});

it('validates time with negative timezone offset', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('08:30:06-08:00'))->toBeTrue()
        ->and($validator->validate('14:30:00-05:00'))->toBeTrue()
        ->and($validator->validate('14:30:00-00:00'))->toBeTrue();
});

it('validates time at midnight', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('00:00:00Z'))->toBeTrue();
});

it('validates time at end of day', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('23:59:59Z'))->toBeTrue();
});

it('validates leap second at 23:59:60 UTC', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('23:59:60Z'))->toBeTrue();
});

it('validates leap second with timezone offset converting to 23:59 UTC', function (): void {
    $validator = new TimeFormatValidator();

    // 15:59:60 PST (-08:00) = 23:59:60 UTC
    expect($validator->validate('15:59:60-08:00'))->toBeTrue();
});

it('rejects invalid hour values', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('24:00:00Z'))->toBeFalse()
        ->and($validator->validate('25:30:00Z'))->toBeFalse()
        ->and($validator->validate('99:00:00Z'))->toBeFalse();
});

it('rejects invalid minute values', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:60:00Z'))->toBeFalse()
        ->and($validator->validate('14:99:00Z'))->toBeFalse();
});

it('rejects invalid second values', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:61Z'))->toBeFalse()
        ->and($validator->validate('14:30:99Z'))->toBeFalse();
});

it('rejects leap second not at 23:59 UTC', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:60Z'))->toBeFalse()
        ->and($validator->validate('23:58:60Z'))->toBeFalse()
        ->and($validator->validate('22:59:60Z'))->toBeFalse();
});

it('rejects invalid timezone offset hours', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:00+24:00'))->toBeFalse()
        ->and($validator->validate('14:30:00+25:00'))->toBeFalse();
});

it('rejects invalid timezone offset minutes', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:00+05:60'))->toBeFalse()
        ->and($validator->validate('14:30:00+05:99'))->toBeFalse();
});

it('rejects time without timezone', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:00'))->toBeFalse()
        ->and($validator->validate('08:30:06'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['08:30:06Z']))->toBeFalse();
});

it('validates time with fractional seconds and timezone offset', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('14:30:00.123+05:30'))->toBeTrue()
        ->and($validator->validate('14:30:00.999999-08:00'))->toBeTrue();
});

it('rejects non-padded time components', function (): void {
    $validator = new TimeFormatValidator();

    expect($validator->validate('8:30:06Z'))->toBeFalse()
        ->and($validator->validate('08:3:06Z'))->toBeFalse()
        ->and($validator->validate('08:30:6Z'))->toBeFalse();
});

it('rejects non-ascii digits in time', function (): void {
    $validator = new TimeFormatValidator();

    // Bengali digits (U+09E6-U+09EF)
    expect($validator->validate("\u{09E7}\u{09E8}:30:06Z"))->toBeFalse()
        // Arabic-Indic digits
        ->and($validator->validate("\u{0661}\u{0662}:30:06Z"))->toBeFalse();
});

it('validates times with underflow to previous day', function (): void {
    $validator = new TimeFormatValidator();

    // 00:00:00+01:00 = 23:00:00 UTC (previous day)
    expect($validator->validate('00:00:00+01:00'))->toBeTrue()
        // 01:30:00+05:00 = 20:30:00 UTC (previous day)
        ->and($validator->validate('01:30:00+05:00'))->toBeTrue();
});

it('rejects leap second with invalid timezone offset hours during validation', function (): void {
    $validator = new TimeFormatValidator();

    // Testing timezone offset hour validation during leap second check
    // These have second=60 which triggers timezone offset validation
    expect($validator->validate('23:59:60+24:00'))->toBeFalse()
        ->and($validator->validate('23:59:60+99:00'))->toBeFalse();
});

it('rejects leap second with invalid timezone offset minutes during validation', function (): void {
    $validator = new TimeFormatValidator();

    // Testing timezone offset minute validation during leap second check
    expect($validator->validate('23:59:60+05:60'))->toBeFalse()
        ->and($validator->validate('23:59:60+05:99'))->toBeFalse();
});

it('validates leap second with timezone causing time underflow', function (): void {
    $validator = new TimeFormatValidator();

    // 00:59:60+01:00 would convert to 23:59:60 UTC (previous day) which is valid
    expect($validator->validate('00:59:60+01:00'))->toBeTrue();
});
