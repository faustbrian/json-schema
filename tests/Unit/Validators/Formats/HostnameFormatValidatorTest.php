<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\HostnameFormatValidator;

use function expect;
use function it;
use function str_repeat;

it('returns correct format name', function (): void {
    $validator = new HostnameFormatValidator();
    expect($validator->format())->toBe('hostname');
});

it('validates simple hostnames', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example.com'))->toBeTrue()
        ->and($validator->validate('localhost'))->toBeTrue()
        ->and($validator->validate('api.example.com'))->toBeTrue()
        ->and($validator->validate('sub.domain.example.com'))->toBeTrue();
});

it('validates hostnames with hyphens in middle of labels', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('api-v2.example.com'))->toBeTrue()
        ->and($validator->validate('my-server.example.com'))->toBeTrue()
        ->and($validator->validate('sub-domain-test.example.com'))->toBeTrue();
});

it('validates hostnames with numeric characters', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('server1.example.com'))->toBeTrue()
        ->and($validator->validate('api2.example.com'))->toBeTrue()
        ->and($validator->validate('web123.example.com'))->toBeTrue();
});

it('validates single label hostnames', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('localhost'))->toBeTrue()
        ->and($validator->validate('hostname'))->toBeTrue();
});

it('validates hostnames at maximum length', function (): void {
    $validator = new HostnameFormatValidator();

    // 253 characters total (max allowed)
    $hostname = str_repeat('a', 63).'.'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 59);
    expect($validator->validate($hostname))->toBeTrue();
});

it('validates labels at maximum length', function (): void {
    $validator = new HostnameFormatValidator();

    // 63 character label (max allowed)
    $hostname = str_repeat('a', 63).'.example.com';
    expect($validator->validate($hostname))->toBeTrue();
});

it('rejects empty hostnames', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects hostnames exceeding maximum length', function (): void {
    $validator = new HostnameFormatValidator();

    // 254 characters (exceeds 253 max)
    $hostname = str_repeat('a', 63).'.'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 62);
    expect($validator->validate($hostname))->toBeFalse();
});

it('rejects labels exceeding maximum length', function (): void {
    $validator = new HostnameFormatValidator();

    // 64 character label (exceeds 63 max)
    $hostname = str_repeat('a', 64).'.example.com';
    expect($validator->validate($hostname))->toBeFalse();
});

it('rejects hostnames starting with dot', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('.example.com'))->toBeFalse()
        ->and($validator->validate('.localhost'))->toBeFalse();
});

it('rejects hostnames ending with dot', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example.com.'))->toBeFalse()
        ->and($validator->validate('localhost.'))->toBeFalse();
});

it('rejects labels starting with hyphen', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('-example.com'))->toBeFalse()
        ->and($validator->validate('api.-example.com'))->toBeFalse();
});

it('rejects labels ending with hyphen', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example-.com'))->toBeFalse()
        ->and($validator->validate('api.example-.com'))->toBeFalse();
});

it('rejects hostnames with consecutive dots', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example..com'))->toBeFalse()
        ->and($validator->validate('api...example.com'))->toBeFalse();
});

it('rejects hostnames with invalid characters', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example_test.com'))->toBeFalse()
        ->and($validator->validate('api@example.com'))->toBeFalse()
        ->and($validator->validate('example!.com'))->toBeFalse()
        ->and($validator->validate('example .com'))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['example.com']))->toBeFalse()
        ->and($validator->validate((object) ['host' => 'example.com']))->toBeFalse();
});

it('validates mixed case hostnames', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('Example.COM'))->toBeTrue()
        ->and($validator->validate('API.Example.Com'))->toBeTrue();
});

it('rejects hostnames with empty labels', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('example..com'))->toBeFalse();
});

it('rejects single dot as hostname', function (): void {
    $validator = new HostnameFormatValidator();

    expect($validator->validate('.'))->toBeFalse();
});

it('rejects hostnames with fullwidth stop character', function (): void {
    $validator = new HostnameFormatValidator();

    // U+FF0E fullwidth full stop
    expect($validator->validate("example\u{FF0E}com"))->toBeFalse()
        ->and($validator->validate("test\u{FF0E}example.com"))->toBeFalse();
});

it('rejects labels with double-dash in positions 3-4 that are not punycode', function (): void {
    $validator = new HostnameFormatValidator();

    // Double-dash at positions 3-4 (indices 2-3) should be rejected unless it's xn--
    expect($validator->validate('ab--cd.example.com'))->toBeFalse()
        ->and($validator->validate('aa--bb.com'))->toBeFalse()
        ->and($validator->validate('zz--yy.example.com'))->toBeFalse();
});

it('validates valid punycode labels', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--mnchen-3ya = münchen
    expect($validator->validate('xn--mnchen-3ya.de'))->toBeTrue()
        // xn--wgbl6a = مثال (example in Arabic)
        ->and($validator->validate('xn--wgbl6a.example'))->toBeTrue()
        // xn--n3h = ☃ (snowman)
        ->and($validator->validate('xn--n3h.com'))->toBeTrue();
});

it('rejects punycode labels that are too short', function (): void {
    $validator = new HostnameFormatValidator();

    // xn-- alone is only 4 characters (need at least 5)
    expect($validator->validate('xn--.com'))->toBeFalse()
        ->and($validator->validate('xn--a'))->toBeFalse();
});

it('validates uppercase punycode labels', function (): void {
    $validator = new HostnameFormatValidator();

    // XN-- (uppercase) should also be recognized as punycode
    expect($validator->validate('XN--mnchen-3ya.de'))->toBeTrue()
        ->and($validator->validate('XN--WGBL6A.example'))->toBeTrue();
});

it('rejects invalid punycode that fails decoding', function (): void {
    $validator = new HostnameFormatValidator();

    // Invalid punycode sequences that will fail idn_to_utf8
    expect($validator->validate('xn--99999999999.com'))->toBeFalse();
});

it('rejects punycode with invalid characters after re-encoding', function (): void {
    $validator = new HostnameFormatValidator();

    // These should fail IDNA validation during re-encoding
    // xn--a-ecp.ru contains disallowed character
    expect($validator->validate('xn--a-ecp.ru'))->toBeFalse();
});

it('validates punycode labels shorter than 4 characters when xn-- prefix', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--a is 5 characters, should go through punycode validation
    // but will fail because it's invalid punycode
    expect($validator->validate('xn--a.com'))->toBeFalse();
});

// IDNA2008 Contextual Rules Tests

it('validates MIDDLE DOT between two lowercase l characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--lla-lga = l·la (MIDDLE DOT U+00B7 between two lowercase 'l')
    expect($validator->validate('xn--lla-lga.cat'))->toBeTrue();
});

it('rejects MIDDLE DOT not between two lowercase l characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ab-0ea = a·b (MIDDLE DOT without proper context should fail)
    expect($validator->validate('xn--ab-0ea.com'))->toBeFalse();
});

it('rejects MIDDLE DOT at start or end of label', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--l-fda = ·l (MIDDLE DOT at start)
    expect($validator->validate('xn--l-fda.com'))->toBeFalse();

    // xn--l-gda = l· (MIDDLE DOT at end)
    expect($validator->validate('xn--l-gda.com'))->toBeFalse();
});

it('validates GREEK LOWER NUMERAL SIGN followed by Greek character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--wva3je = α͵β (U+0375 KERAIA followed by Greek character)
    expect($validator->validate('xn--wva3je.gr'))->toBeTrue();
});

it('rejects GREEK LOWER NUMERAL SIGN without following Greek character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ab-63b = a͵b (KERAIA not followed by Greek character should fail)
    expect($validator->validate('xn--ab-63b.com'))->toBeFalse();
});

it('rejects GREEK LOWER NUMERAL SIGN at end of label', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--wva3j = α͵ (KERAIA at end of label, no following character)
    expect($validator->validate('xn--wva3j.com'))->toBeFalse();
});

it('validates HEBREW PUNCTUATION GERESH preceded by Hebrew character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--4dbc5h = א׳ב (U+05F3 GERESH preceded by Hebrew character)
    expect($validator->validate('xn--4dbc5h.il'))->toBeTrue();
});

it('validates HEBREW PUNCTUATION GERSHAYIM preceded by Hebrew character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--4dbc8h = א״ב (U+05F4 GERSHAYIM preceded by Hebrew character)
    expect($validator->validate('xn--4dbc8h.il'))->toBeTrue();
});

it('rejects HEBREW PUNCTUATION at start of label', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--4db3e = ׳א (GERESH at start, no preceding Hebrew character)
    expect($validator->validate('xn--4db3e.com'))->toBeFalse();
});

it('rejects HEBREW PUNCTUATION not preceded by Hebrew character', function (): void {
    $validator = new HostnameFormatValidator();

    // GERESH/GERSHAYIM preceded by non-Hebrew character should fail
    expect($validator->validate('xn--a-zsb.com'))->toBeFalse();
});

it('validates Hangul combining marks preceded by Hangul Jamo', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ypd870n = ᄀ〮 (U+302E tone mark preceded by Hangul Jamo)
    expect($validator->validate('xn--ypd870n.kr'))->toBeTrue();
});

it('rejects Hangul combining marks not preceded by Hangul Jamo', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--07jt248a = 가〮 (tone mark preceded by Hangul Syllable, not Jamo - should fail)
    expect($validator->validate('xn--07jt248a.com'))->toBeFalse();
});

it('validates KATAKANA MIDDLE DOT with Japanese characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ccke4x = ア・イ (U+30FB KATAKANA MIDDLE DOT with Katakana)
    expect($validator->validate('xn--ccke4x.jp'))->toBeTrue();
});

it('rejects KATAKANA MIDDLE DOT without Japanese characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ab-3n4a = a・b (KATAKANA MIDDLE DOT with Latin should fail)
    expect($validator->validate('xn--ab-3n4a.com'))->toBeFalse();
});

it('validates KATAKANA MIDDLE DOT with Hiragana characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--l8je26c = あ・い (U+30FB with Hiragana)
    expect($validator->validate('xn--l8je26c.jp'))->toBeTrue();
});

it('validates KATAKANA MIDDLE DOT with Han characters', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--vekv29fo7f = 中・国 (U+30FB with Han/CJK ideographs)
    expect($validator->validate('xn--vekv29fo7f.cn'))->toBeTrue();
});

it('validates Arabic-Indic digits without mixing', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--pgb2ide = ت٠١٢ (Arabic text with U+0660-U+0669 Arabic-Indic digits only)
    expect($validator->validate('xn--pgb2ide.ar'))->toBeTrue();
});

it('validates Extended Arabic-Indic digits without mixing', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--pgb01bde = ت۰۱۲ (Arabic text with U+06F0-U+06F9 Extended Arabic-Indic digits only)
    expect($validator->validate('xn--pgb01bde.ir'))->toBeTrue();
});

it('rejects mixed Arabic-Indic and Extended Arabic-Indic digits', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--pgb2i1r = ت٠۱ (mixing U+0660-U+0669 with U+06F0-U+06F9 should fail)
    expect($validator->validate('xn--pgb2i1r.com'))->toBeFalse();
});

it('rejects ARABIC TATWEEL character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--mgbc5e = اـب (U+0640 ARABIC TATWEEL is disallowed in IDNA2008)
    expect($validator->validate('xn--mgbc5e.com'))->toBeFalse();
});

it('rejects NKO LAJANYALAN character', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--ytb = ߺ (U+07FA NKO LAJANYALAN is disallowed)
    expect($validator->validate('xn--ytb.com'))->toBeFalse();
});

it('rejects disallowed CJK mark U+3031', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--37j = 〱 (U+3031 is disallowed)
    expect($validator->validate('xn--37j.com'))->toBeFalse();
});

it('rejects disallowed CJK mark U+303B', function (): void {
    $validator = new HostnameFormatValidator();

    // xn--e8j = 〻 (U+303B is disallowed)
    expect($validator->validate('xn--e8j.com'))->toBeFalse();
});
