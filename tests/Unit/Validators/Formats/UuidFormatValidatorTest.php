<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\UuidFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new UuidFormatValidator();
    expect($validator->format())->toBe('uuid');
});

it('validates lowercase UUID v4', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue()
        ->and($validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'))->toBeTrue()
        ->and($validator->validate('123e4567-e89b-12d3-a456-426614174000'))->toBeTrue();
});

it('validates uppercase UUID v4', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550E8400-E29B-41D4-A716-446655440000'))->toBeTrue()
        ->and($validator->validate('6BA7B810-9DAD-11D1-80B4-00C04FD430C8'))->toBeTrue();
});

it('validates mixed case UUID v4', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-E29B-41d4-A716-446655440000'))->toBeTrue()
        ->and($validator->validate('6ba7B810-9Dad-11D1-80b4-00C04fd430c8'))->toBeTrue();
});

it('validates UUID v1 format', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'))->toBeTrue();
});

it('validates UUID v3 format', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('6fa459ea-ee8a-3ca4-894e-db77e160355e'))->toBeTrue();
});

it('validates UUID v5 format', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('886313e1-3b8a-5372-9b90-0c9aee199e5d'))->toBeTrue();
});

it('validates nil UUID', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('00000000-0000-0000-0000-000000000000'))->toBeTrue();
});

it('validates UUIDs with all zeros in segments', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('00000000-0000-0000-0000-000000000000'))->toBeTrue()
        ->and($validator->validate('12345678-0000-0000-0000-000000000000'))->toBeTrue();
});

it('validates UUIDs with all fs in segments', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('ffffffff-ffff-ffff-ffff-ffffffffffff'))->toBeTrue()
        ->and($validator->validate('FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF'))->toBeTrue();
});

it('rejects UUIDs without hyphens', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400e29b41d4a716446655440000'))->toBeFalse();
});

it('rejects UUIDs with incorrect hyphen positions', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b41-d4a7-16-446655440000'))->toBeFalse()
        ->and($validator->validate('550e84-00e2-9b41-d4a7-16446655440000'))->toBeFalse();
});

it('rejects UUIDs with too few segments', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b-41d4-a716'))->toBeFalse()
        ->and($validator->validate('550e8400-e29b-41d4'))->toBeFalse();
});

it('rejects UUIDs with too many segments', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000-extra'))->toBeFalse();
});

it('rejects UUIDs with invalid characters', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b-41d4-a716-44665544000g'))->toBeFalse()
        ->and($validator->validate('550e8400-e29b-41d4-a716-44665544000z'))->toBeFalse()
        ->and($validator->validate('gggggggg-gggg-gggg-gggg-gggggggggggg'))->toBeFalse();
});

it('rejects UUIDs with incorrect segment lengths', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e840-e29b-41d4-a716-446655440000'))->toBeFalse()
        ->and($validator->validate('550e84000-e29b-41d4-a716-446655440000'))->toBeFalse()
        ->and($validator->validate('550e8400-e29-41d4-a716-446655440000'))->toBeFalse()
        ->and($validator->validate('550e8400-e29bb-41d4-a716-446655440000'))->toBeFalse();
});

it('rejects UUIDs with spaces', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400 e29b-41d4-a716-446655440000'))->toBeFalse()
        ->and($validator->validate('550e8400-e29b-41d4-a716-446655440000 '))->toBeFalse()
        ->and($validator->validate(' 550e8400-e29b-41d4-a716-446655440000'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['550e8400-e29b-41d4-a716-446655440000']))->toBeFalse()
        ->and($validator->validate((object) ['uuid' => '550e8400-e29b-41d4-a716-446655440000']))->toBeFalse();
});

it('rejects UUIDs with curly braces', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('{550e8400-e29b-41d4-a716-446655440000}'))->toBeFalse();
});

it('rejects UUIDs with URN prefix', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('urn:uuid:550e8400-e29b-41d4-a716-446655440000'))->toBeFalse();
});

it('validates UUID with exactly 36 characters', function (): void {
    $validator = new UuidFormatValidator();

    // Valid: 8-4-4-4-12 = 32 hex + 4 hyphens = 36 chars
    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue();
});

it('rejects UUID-like strings with special characters', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400@e29b-41d4-a716-446655440000'))->toBeFalse()
        ->and($validator->validate('550e8400_e29b-41d4-a716-446655440000'))->toBeFalse();
});

it('validates version 2 UUIDs', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('000003e8-2363-21ed-b200-325096b39f47'))->toBeTrue();
});

it('rejects truncated UUIDs', function (): void {
    $validator = new UuidFormatValidator();

    expect($validator->validate('550e8400-e29b-41d4-a716-44665544000'))->toBeFalse()
        ->and($validator->validate('550e8400-e29b-41d4-a716-4466554400'))->toBeFalse();
});
