<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\DurationFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new DurationFormatValidator();
    expect($validator->format())->toBe('duration');
});

it('validates complete ISO 8601 duration', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P3Y6M4DT12H30M5S'))->toBeTrue()
        ->and($validator->validate('P1Y2M3DT4H5M6S'))->toBeTrue();
});

it('validates duration with only date components', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P3Y'))->toBeTrue()
        ->and($validator->validate('P6M'))->toBeTrue()
        ->and($validator->validate('P4D'))->toBeTrue()
        ->and($validator->validate('P1Y2M'))->toBeTrue()
        ->and($validator->validate('P1Y3D'))->toBeTrue();
});

it('validates duration with only time components', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('PT12H'))->toBeTrue()
        ->and($validator->validate('PT30M'))->toBeTrue()
        ->and($validator->validate('PT5S'))->toBeTrue()
        ->and($validator->validate('PT0S'))->toBeTrue()
        ->and($validator->validate('PT12H30M'))->toBeTrue();
});

it('validates duration with mixed date and time components', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P23DT23H'))->toBeTrue()
        ->and($validator->validate('P1YT1H'))->toBeTrue()
        ->and($validator->validate('P1Y2M3DT4H5M6S'))->toBeTrue();
});

it('validates week-based durations', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P1W'))->toBeTrue()
        ->and($validator->validate('P52W'))->toBeTrue()
        ->and($validator->validate('P100W'))->toBeTrue();
});

it('validates duration with decimal seconds', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('PT0.5S'))->toBeTrue()
        ->and($validator->validate('PT1.234S'))->toBeTrue();
});

it('rejects duration without P prefix', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('1Y2M3D'))->toBeFalse()
        ->and($validator->validate('T1H2M3S'))->toBeFalse();
});

it('rejects duration with only P', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P'))->toBeFalse()
        ->and($validator->validate('PT'))->toBeFalse();
});

it('rejects duration with invalid format', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate('P1.5Y'))->toBeFalse()
        ->and($validator->validate('P1Y2M3DT'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new DurationFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['P1Y']))->toBeFalse();
});
