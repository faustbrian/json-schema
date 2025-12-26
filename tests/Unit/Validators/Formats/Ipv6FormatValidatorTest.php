<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\Ipv6FormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new Ipv6FormatValidator();
    expect($validator->format())->toBe('ipv6');
});

it('validates full notation IPv6 addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->toBeTrue()
        ->and($validator->validate('2001:0db8:0000:0000:0000:0000:0000:0001'))->toBeTrue()
        ->and($validator->validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156'))->toBeTrue();
});

it('validates compressed notation IPv6 addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3::8a2e:370:7334'))->toBeTrue()
        ->and($validator->validate('2001:db8::1'))->toBeTrue()
        ->and($validator->validate('fe80::1'))->toBeTrue();
});

it('validates loopback address', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::1'))->toBeTrue()
        ->and($validator->validate('0000:0000:0000:0000:0000:0000:0000:0001'))->toBeTrue();
});

it('validates unspecified address', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::'))->toBeTrue()
        ->and($validator->validate('0000:0000:0000:0000:0000:0000:0000:0000'))->toBeTrue();
});

it('validates mixed IPv4/IPv6 notation', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.0.2.1'))->toBeTrue()
        ->and($validator->validate('::192.0.2.1'))->toBeTrue()
        ->and($validator->validate('64:ff9b::192.0.2.33'))->toBeTrue();
});

it('validates link-local addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('fe80::'))->toBeTrue()
        ->and($validator->validate('fe80::1'))->toBeTrue()
        ->and($validator->validate('fe80::204:61ff:fe9d:f156'))->toBeTrue();
});

it('validates multicast addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('ff00::'))->toBeTrue()
        ->and($validator->validate('ff02::1'))->toBeTrue()
        ->and($validator->validate('ff02::2'))->toBeTrue();
});

it('validates unique local addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('fc00::'))->toBeTrue()
        ->and($validator->validate('fd00::1'))->toBeTrue();
});

it('validates addresses with leading zeros omitted', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3:0:0:8a2e:370:7334'))->toBeTrue()
        ->and($validator->validate('2001:db8:0:0:0:0:0:1'))->toBeTrue();
});

it('rejects invalid IPv6 addresses with too many groups', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334:extra'))->toBeFalse();
});

it('rejects invalid IPv6 addresses with invalid characters', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:0db8:85g3::8a2e:370:7334'))->toBeFalse()
        ->and($validator->validate('gggg::1'))->toBeFalse()
        ->and($validator->validate('2001:xyz::1'))->toBeFalse();
});

it('rejects invalid IPv6 addresses with multiple double colons', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001::db8::1'))->toBeFalse()
        ->and($validator->validate('::1::'))->toBeFalse();
});

it('rejects invalid IPv6 addresses with groups exceeding four hex digits', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:0db8:85a3:00000:0000:8a2e:0370:7334'))->toBeFalse()
        ->and($validator->validate('2001:12345::1'))->toBeFalse();
});

it('rejects invalid IPv6 addresses with trailing colon', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3::8a2e:370:7334:'))->toBeFalse();
});

it('rejects invalid IPv6 addresses with leading single colon', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate(':2001:db8:85a3::8a2e:370:7334'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['2001:db8::1']))->toBeFalse()
        ->and($validator->validate((object) ['ip' => '2001:db8::1']))->toBeFalse();
});

it('rejects IPv4 addresses', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('192.168.1.1'))->toBeFalse()
        ->and($validator->validate('10.0.0.1'))->toBeFalse();
});

it('validates uppercase hexadecimal digits', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:DB8:85A3::8A2E:370:7334'))->toBeTrue()
        ->and($validator->validate('FE80::1'))->toBeTrue();
});

it('validates mixed case hexadecimal digits', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:Db8:85a3::8A2e:370:7334'))->toBeTrue();
});

it('rejects IPv6 addresses with leading whitespace', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate(' 2001:db8::1'))->toBeFalse()
        ->and($validator->validate("\t2001:db8::1"))->toBeFalse()
        ->and($validator->validate("\n2001:db8::1"))->toBeFalse();
});

it('rejects IPv6 addresses with trailing whitespace', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8::1 '))->toBeFalse()
        ->and($validator->validate("2001:db8::1\t"))->toBeFalse()
        ->and($validator->validate("2001:db8::1\n"))->toBeFalse();
});

it('rejects IPv6 addresses with internal whitespace', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8 ::1'))->toBeFalse()
        ->and($validator->validate('2001: db8::1'))->toBeFalse()
        ->and($validator->validate('2001:db8: :1'))->toBeFalse();
});

it('rejects IPv6 addresses with non-ASCII characters', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8::1α'))->toBeFalse()
        ->and($validator->validate('2001:dß8::1'))->toBeFalse()
        ->and($validator->validate('2001:db8:🔥:1'))->toBeFalse()
        ->and($validator->validate('２001:db8::1'))->toBeFalse()
        ->and($validator->validate('2001:db8::１'))->toBeFalse();
});

it('rejects IPv6 addresses with zone IDs', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('fe80::1%eth0'))->toBeFalse()
        ->and($validator->validate('fe80::a%eth1'))->toBeFalse()
        ->and($validator->validate('fe80::204:61ff:fe9d:f156%en0'))->toBeFalse()
        ->and($validator->validate('::1%lo'))->toBeFalse();
});

it('rejects IPv6 addresses with CIDR notation', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8::/32'))->toBeFalse()
        ->and($validator->validate('fe80::/64'))->toBeFalse()
        ->and($validator->validate('::1/128'))->toBeFalse()
        ->and($validator->validate('::/0'))->toBeFalse();
});

it('rejects IPv6 addresses with triple colons', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:::1'))->toBeFalse()
        ->and($validator->validate(':::1'))->toBeFalse()
        ->and($validator->validate('2001:db8:::'))->toBeFalse()
        ->and($validator->validate('fe80::::1'))->toBeFalse();
});

it('rejects mixed format with invalid IPv4 part - empty octet', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192..1.1'))->toBeFalse()
        ->and($validator->validate('::ffff:.0.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.0.2.'))->toBeFalse();
});

it('rejects mixed format with invalid IPv4 part - non-digit characters', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.0.2.a'))->toBeFalse()
        ->and($validator->validate('::ffff:abc.0.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.x.2.1'))->toBeFalse();
});

it('rejects mixed format with invalid IPv4 part - leading zeros', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.01.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.0.02.1'))->toBeFalse()
        ->and($validator->validate('::ffff:001.0.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.000.2.1'))->toBeFalse();
});

it('rejects mixed format with invalid IPv4 part - out of range octets', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:256.0.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.256.2.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.0.256.1'))->toBeFalse()
        ->and($validator->validate('::ffff:192.0.2.256'))->toBeFalse()
        ->and($validator->validate('::ffff:999.0.2.1'))->toBeFalse();
});

it('rejects mixed format with too few IPv4 octets', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.0.2'))->toBeFalse()
        ->and($validator->validate('::ffff:192.0'))->toBeFalse()
        ->and($validator->validate('64:ff9b::192.0'))->toBeFalse();
});

it('rejects mixed format with too many IPv4 octets', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.0.2.1.5'))->toBeFalse();
});

it('rejects mixed format with too many IPv6 groups without compression', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3:0:0:8a2e:370:192.0.2.1'))->toBeFalse()
        ->and($validator->validate('1:2:3:4:5:6:7:192.0.2.1'))->toBeFalse();
});

it('rejects mixed format with too many IPv6 groups with compression', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3:0:0:8a2e:370::192.0.2.1'))->toBeFalse()
        ->and($validator->validate('1:2:3:4:5:6:7::192.0.2.1'))->toBeFalse();
});

it('validates mixed format with exactly 6 IPv6 groups without compression', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3:0:8a2e:370:192.0.2.1'))->toBeTrue()
        ->and($validator->validate('1:2:3:4:5:6:192.0.2.1'))->toBeTrue();
});

it('validates mixed format with fewer than 6 IPv6 groups with compression', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('64:ff9b::192.0.2.33'))->toBeTrue()
        ->and($validator->validate('::1:192.0.2.1'))->toBeTrue()
        ->and($validator->validate('1::192.0.2.1'))->toBeTrue();
});

it('rejects mixed format with invalid hex groups', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('gggg::192.0.2.1'))->toBeFalse()
        ->and($validator->validate('12345::192.0.2.1'))->toBeFalse()
        ->and($validator->validate('xyz::192.0.2.1'))->toBeFalse();
});

it('validates mixed format with valid IPv4 zero octet', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:192.0.0.1'))->toBeTrue()
        ->and($validator->validate('::ffff:0.0.0.0'))->toBeTrue();
});

it('validates mixed format with IPv4 boundary values', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::ffff:255.255.255.255'))->toBeTrue()
        ->and($validator->validate('::ffff:0.0.0.1'))->toBeTrue()
        ->and($validator->validate('::ffff:127.0.0.1'))->toBeTrue();
});

it('rejects pure IPv6 with too few groups without compression', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('2001:db8:85a3:0:0:8a2e:370'))->toBeFalse()
        ->and($validator->validate('1:2:3:4:5:6:7'))->toBeFalse();
});

it('validates pure IPv6 with compression at various positions', function (): void {
    $validator = new Ipv6FormatValidator();

    expect($validator->validate('::8a2e:370:7334'))->toBeTrue()
        ->and($validator->validate('2001::'))->toBeTrue()
        ->and($validator->validate('2001:db8::8a2e:370:7334'))->toBeTrue();
});
