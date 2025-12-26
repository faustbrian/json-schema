<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\EmailFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new EmailFormatValidator();
    expect($validator->format())->toBe('email');
});

it('validates standard email addresses', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user@example.com'))->toBeTrue()
        ->and($validator->validate('john.doe@example.com'))->toBeTrue()
        ->and($validator->validate('test_user@example.com'))->toBeTrue()
        ->and($validator->validate('user123@example.com'))->toBeTrue();
});

it('validates email addresses with subdomains', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user@mail.example.com'))->toBeTrue()
        ->and($validator->validate('admin@sub.domain.example.com'))->toBeTrue();
});

it('validates email addresses with plus addressing', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user+tag@example.com'))->toBeTrue()
        ->and($validator->validate('john+test+123@example.com'))->toBeTrue();
});

it('rejects email addresses with numeric IP domains', function (): void {
    $validator = new EmailFormatValidator();

    // PHP's FILTER_VALIDATE_EMAIL doesn't accept IP addresses as domains
    expect($validator->validate('user@192.168.1.1'))->toBeFalse();
});

it('validates email addresses with special characters in local part', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user.name@example.com'))->toBeTrue()
        ->and($validator->validate('user_name@example.com'))->toBeTrue()
        ->and($validator->validate('user-name@example.com'))->toBeTrue();
});

it('rejects invalid email addresses without @ symbol', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('userexample.com'))->toBeFalse()
        ->and($validator->validate('user.example.com'))->toBeFalse();
});

it('rejects invalid email addresses with multiple @ symbols', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user@@example.com'))->toBeFalse()
        ->and($validator->validate('user@test@example.com'))->toBeFalse();
});

it('rejects invalid email addresses with spaces', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user @example.com'))->toBeFalse()
        ->and($validator->validate('user@ example.com'))->toBeFalse()
        ->and($validator->validate('user name@example.com'))->toBeFalse();
});

it('rejects invalid email addresses with invalid domain', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user@'))->toBeFalse()
        ->and($validator->validate('user@.com'))->toBeFalse()
        ->and($validator->validate('user@example.'))->toBeFalse()
        ->and($validator->validate('@example.com'))->toBeFalse();
});

it('rejects empty strings', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate(''))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['email@example.com']))->toBeFalse()
        ->and($validator->validate((object) ['email' => 'test@example.com']))->toBeFalse();
});

it('rejects invalid email addresses with consecutive dots', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('user..name@example.com'))->toBeFalse()
        ->and($validator->validate('user@example..com'))->toBeFalse();
});

it('rejects invalid email addresses starting or ending with dot', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('.user@example.com'))->toBeFalse()
        ->and($validator->validate('user.@example.com'))->toBeFalse();
});

it('validates case-sensitive email addresses', function (): void {
    $validator = new EmailFormatValidator();

    expect($validator->validate('User@Example.COM'))->toBeTrue()
        ->and($validator->validate('ADMIN@EXAMPLE.COM'))->toBeTrue();
});

it('validates quoted string email addresses', function (): void {
    $validator = new EmailFormatValidator();

    // RFC 5322 allows quoted strings in the local part
    expect($validator->validate('"user"@example.com'))->toBeTrue()
        ->and($validator->validate('"user.name"@example.com'))->toBeTrue()
        ->and($validator->validate('"user@name"@example.com'))->toBeTrue()
        ->and($validator->validate('"user name"@example.com'))->toBeTrue();
});

it('rejects quoted string emails with invalid domain', function (): void {
    $validator = new EmailFormatValidator();

    // Quoted local part but invalid domain
    expect($validator->validate('"user"@'))->toBeFalse()
        ->and($validator->validate('"user"@.com'))->toBeFalse()
        ->and($validator->validate('"user"@-example.com'))->toBeFalse()
        ->and($validator->validate('"user"@example-.com'))->toBeFalse();
});
