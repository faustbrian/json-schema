<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\Ipv4FormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new Ipv4FormatValidator();
    expect($validator->format())->toBe('ipv4');
});

it('validates standard IPv4 addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1.1'))->toBeTrue()
        ->and($validator->validate('10.0.0.1'))->toBeTrue()
        ->and($validator->validate('172.16.0.1'))->toBeTrue()
        ->and($validator->validate('8.8.8.8'))->toBeTrue();
});

it('validates IPv4 addresses with zeros', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('0.0.0.0'))->toBeTrue()
        ->and($validator->validate('192.0.2.0'))->toBeTrue()
        ->and($validator->validate('10.0.0.0'))->toBeTrue();
});

it('validates IPv4 addresses at boundary values', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('255.255.255.255'))->toBeTrue()
        ->and($validator->validate('0.0.0.0'))->toBeTrue()
        ->and($validator->validate('255.0.0.0'))->toBeTrue()
        ->and($validator->validate('0.255.255.255'))->toBeTrue();
});

it('validates loopback addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('127.0.0.1'))->toBeTrue()
        ->and($validator->validate('127.0.0.0'))->toBeTrue();
});

it('validates broadcast addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('255.255.255.255'))->toBeTrue();
});

it('rejects IPv4 addresses with octets exceeding 255', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('256.1.1.1'))->toBeFalse()
        ->and($validator->validate('192.256.1.1'))->toBeFalse()
        ->and($validator->validate('192.168.256.1'))->toBeFalse()
        ->and($validator->validate('192.168.1.256'))->toBeFalse();
});

it('rejects IPv4 addresses with negative octets', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('-1.168.1.1'))->toBeFalse()
        ->and($validator->validate('192.-1.1.1'))->toBeFalse()
        ->and($validator->validate('192.168.-1.1'))->toBeFalse()
        ->and($validator->validate('192.168.1.-1'))->toBeFalse();
});

it('rejects incomplete IPv4 addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1'))->toBeFalse()
        ->and($validator->validate('192.168'))->toBeFalse()
        ->and($validator->validate('192'))->toBeFalse();
});

it('rejects IPv4 addresses with too many octets', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1.1.1'))->toBeFalse()
        ->and($validator->validate('192.168.1.1.1.1'))->toBeFalse();
});

it('rejects IPv4 addresses with empty octets', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168..1'))->toBeFalse()
        ->and($validator->validate('.168.1.1'))->toBeFalse()
        ->and($validator->validate('192.168.1.'))->toBeFalse();
});

it('rejects IPv4 addresses with non-numeric characters', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1.a'))->toBeFalse()
        ->and($validator->validate('abc.def.ghi.jkl'))->toBeFalse()
        ->and($validator->validate('192.168.one.1'))->toBeFalse();
});

it('rejects IPv4 addresses with spaces', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1. 1'))->toBeFalse()
        ->and($validator->validate('192. 168.1.1'))->toBeFalse()
        ->and($validator->validate(' 192.168.1.1'))->toBeFalse()
        ->and($validator->validate('192.168.1.1 '))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['192.168.1.1']))->toBeFalse()
        ->and($validator->validate((object) ['ip' => '192.168.1.1']))->toBeFalse();
});

it('rejects IPv6 addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('2001:0db8:85a3::8a2e:0370:7334'))->toBeFalse()
        ->and($validator->validate('::1'))->toBeFalse();
});

it('validates private network addresses', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('10.0.0.0'))->toBeTrue()
        ->and($validator->validate('172.16.0.0'))->toBeTrue()
        ->and($validator->validate('192.168.0.0'))->toBeTrue();
});

it('rejects IPv4 addresses with non-ascii characters', function (): void {
    $validator = new Ipv4FormatValidator();

    // Arabic-Indic digits
    expect($validator->validate("\u{0661}\u{0669}\u{0662}.168.1.1"))->toBeFalse()
        // Unicode characters
        ->and($validator->validate('192.168.1.①'))->toBeFalse();
});

it('rejects CIDR notation', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('192.168.1.0/24'))->toBeFalse()
        ->and($validator->validate('10.0.0.0/8'))->toBeFalse();
});

it('rejects hexadecimal notation', function (): void {
    $validator = new Ipv4FormatValidator();

    expect($validator->validate('0xC0.0xA8.0x01.0x01'))->toBeFalse()
        ->and($validator->validate('192.168.0x1.1'))->toBeFalse();
});

it('rejects IPv4 addresses with leading zeros', function (): void {
    $validator = new Ipv4FormatValidator();

    // Leading zeros are not allowed (except for '0' itself)
    expect($validator->validate('192.168.001.1'))->toBeFalse()
        ->and($validator->validate('192.168.01.1'))->toBeFalse()
        ->and($validator->validate('0192.168.1.1'))->toBeFalse()
        ->and($validator->validate('192.0168.1.1'))->toBeFalse();
});
