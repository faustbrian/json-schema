<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\IdnHostnameFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new IdnHostnameFormatValidator();
    expect($validator->format())->toBe('idn-hostname');
});

it('validates ASCII hostnames', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('example.com'))->toBeTrue()
        ->and($validator->validate('sub.example.com'))->toBeTrue()
        ->and($validator->validate('test-domain.com'))->toBeTrue();
});

it('validates internationalized domain names', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('münchen.de'))->toBeTrue()
        ->and($validator->validate('日本.jp'))->toBeTrue()
        ->and($validator->validate('العربية.example'))->toBeTrue();
});

it('validates punycode encoded hostnames', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('xn--mnchen-3ya.de'))->toBeTrue()
        ->and($validator->validate('xn--wgbl6a.example'))->toBeTrue();
});

it('rejects empty strings', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['example.com']))->toBeFalse();
});

it('rejects hostnames starting with hyphen', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('-example.com'))->toBeFalse();
});

it('rejects hostnames ending with hyphen', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('example-.com'))->toBeFalse();
});

it('rejects hostnames with invalid characters', function (): void {
    $validator = new IdnHostnameFormatValidator();

    expect($validator->validate('exam ple.com'))->toBeFalse()
        ->and($validator->validate('example@.com'))->toBeFalse();
});

it('validates middle dot between lowercase l characters', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+00B7 middle dot is valid between two lowercase 'l' characters (Catalan)
    expect($validator->validate("l\u{00B7}l.example.com"))->toBeTrue();
});

it('rejects middle dot not between lowercase l characters', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+00B7 middle dot must be between two lowercase 'l' characters
    expect($validator->validate("a\u{00B7}b.com"))->toBeFalse()
        ->and($validator->validate("l\u{00B7}a.com"))->toBeFalse()
        ->and($validator->validate("a\u{00B7}l.com"))->toBeFalse();
});

it('validates greek keraia followed by greek character', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+0375 Greek Keraia followed by Greek letter is valid
    expect($validator->validate("\u{0375}\u{03B1}.example.com"))->toBeTrue();
});

it('rejects greek keraia not followed by greek character', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+0375 must be followed by Greek character
    expect($validator->validate("\u{0375}a.com"))->toBeFalse()
        ->and($validator->validate("test\u{0375}.com"))->toBeFalse();
});

it('validates hebrew punctuation preceded by hebrew', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+05F3 (Geresh) and U+05F4 (Gershayim) after Hebrew characters are valid
    expect($validator->validate("\u{05D0}\u{05F3}.example.com"))->toBeTrue()
        ->and($validator->validate("\u{05D0}\u{05F4}.example.com"))->toBeTrue();
});

it('rejects hebrew punctuation not preceded by hebrew', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // Hebrew punctuation must be preceded by Hebrew character
    expect($validator->validate("\u{05F3}.com"))->toBeFalse()
        ->and($validator->validate("a\u{05F3}.com"))->toBeFalse()
        ->and($validator->validate("\u{05F4}.com"))->toBeFalse();
});

it('validates katakana middle dot with japanese scripts', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+30FB Katakana middle dot with Hiragana/Katakana/Han is valid
    expect($validator->validate("\u{30FB}\u{3042}.com"))->toBeTrue() // with Hiragana
        ->and($validator->validate("\u{30FB}\u{30A2}.com"))->toBeTrue() // with Katakana
        ->and($validator->validate("\u{30FB}\u{4E00}.com"))->toBeTrue(); // with Han
});

it('rejects katakana middle dot without japanese scripts', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+30FB without Japanese scripts is invalid
    expect($validator->validate("\u{30FB}abc.com"))->toBeFalse()
        ->and($validator->validate("test\u{30FB}.com"))->toBeFalse();
});

it('rejects arabic tatweel character', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+0640 Arabic Tatweel is disallowed in IDNA2008
    expect($validator->validate("test\u{0640}.com"))->toBeFalse()
        ->and($validator->validate("\u{0640}example.com"))->toBeFalse();
});

it('rejects nko lajanyalan character', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+07FA NKO Lajanyalan is disallowed
    expect($validator->validate("test\u{07FA}.com"))->toBeFalse();
});

it('rejects disallowed cjk marks', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+3031-U+3035 and U+303B are disallowed
    expect($validator->validate("test\u{3031}.com"))->toBeFalse()
        ->and($validator->validate("test\u{3035}.com"))->toBeFalse()
        ->and($validator->validate("test\u{303B}.com"))->toBeFalse();
});

it('validates hangul jamo followed by combining marks', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+302E and U+302F after Hangul Jamo are valid
    expect($validator->validate("\u{1100}\u{302E}.com"))->toBeTrue()
        ->and($validator->validate("\u{1100}\u{302F}.com"))->toBeTrue();
});

it('rejects hangul combining marks not preceded by jamo', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // U+302E and U+302F must be preceded by Hangul Jamo
    expect($validator->validate("\u{302E}.com"))->toBeFalse()
        ->and($validator->validate("a\u{302E}.com"))->toBeFalse()
        ->and($validator->validate("\u{302F}.com"))->toBeFalse();
});

it('rejects mixed arabic-indic digit types', function (): void {
    $validator = new IdnHostnameFormatValidator();

    // Cannot mix Arabic-Indic (U+0660-U+0669) with Extended Arabic-Indic (U+06F0-U+06F9)
    expect($validator->validate("\u{0660}\u{06F0}.com"))->toBeFalse()
        ->and($validator->validate("test\u{0661}\u{06F1}.com"))->toBeFalse();
});
