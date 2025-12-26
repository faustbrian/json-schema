<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Validators\Formats;

use Cline\JsonSchema\Validators\Formats\JsonPointerFormatValidator;

use function expect;
use function it;

it('returns correct format name', function (): void {
    $validator = new JsonPointerFormatValidator();
    expect($validator->format())->toBe('json-pointer');
});

it('validates empty string as whole document reference', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate(''))->toBeTrue();
});

it('validates simple JSON pointers', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo'))->toBeTrue()
        ->and($validator->validate('/bar'))->toBeTrue()
        ->and($validator->validate('/0'))->toBeTrue();
});

it('validates nested JSON pointers', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo/bar'))->toBeTrue()
        ->and($validator->validate('/foo/bar/baz'))->toBeTrue()
        ->and($validator->validate('/a/b/c/d/e'))->toBeTrue();
});

it('validates JSON pointers with array indices', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo/0'))->toBeTrue()
        ->and($validator->validate('/items/5'))->toBeTrue()
        ->and($validator->validate('/data/123/name'))->toBeTrue();
});

it('validates JSON pointers with escaped tilde', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/m~0n'))->toBeTrue()
        ->and($validator->validate('/foo~0bar'))->toBeTrue()
        ->and($validator->validate('/~0'))->toBeTrue();
});

it('validates JSON pointers with escaped slash', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/a~1b'))->toBeTrue()
        ->and($validator->validate('/foo~1bar'))->toBeTrue()
        ->and($validator->validate('/~1'))->toBeTrue();
});

it('validates JSON pointers with both escape sequences', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/~0~1'))->toBeTrue()
        ->and($validator->validate('/foo~0bar~1baz'))->toBeTrue();
});

it('validates JSON pointers with special characters', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo-bar'))->toBeTrue()
        ->and($validator->validate('/foo_bar'))->toBeTrue()
        ->and($validator->validate('/foo.bar'))->toBeTrue()
        ->and($validator->validate('/foo bar'))->toBeTrue();
});

it('rejects JSON pointers not starting with slash', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('foo'))->toBeFalse()
        ->and($validator->validate('foo/bar'))->toBeFalse()
        ->and($validator->validate('a/b/c'))->toBeFalse();
});

it('rejects URI Fragment Identifier syntax', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('#/foo'))->toBeFalse()
        ->and($validator->validate('#/foo/bar'))->toBeFalse()
        ->and($validator->validate('#'))->toBeFalse();
});

it('rejects JSON pointers with incomplete escape sequences', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo~'))->toBeFalse()
        ->and($validator->validate('/bar~baz'))->toBeFalse();
});

it('rejects JSON pointers with invalid escape sequences', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo~2'))->toBeFalse()
        ->and($validator->validate('/foo~3bar'))->toBeFalse()
        ->and($validator->validate('/~9'))->toBeFalse()
        ->and($validator->validate('/~a'))->toBeFalse();
});

it('rejects JSON pointers ending with tilde', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/foo/bar~'))->toBeFalse()
        ->and($validator->validate('/~'))->toBeFalse();
});

it('rejects non-string values', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate(null))->toBeFalse()
        ->and($validator->validate(123))->toBeFalse()
        ->and($validator->validate(true))->toBeFalse()
        ->and($validator->validate(['/foo']))->toBeFalse()
        ->and($validator->validate((object) ['pointer' => '/foo']))->toBeFalse();
});

it('validates JSON pointers with unicode characters', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/föo'))->toBeTrue()
        ->and($validator->validate('/café'))->toBeTrue();
});

it('validates complex real-world JSON pointers', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/definitions/person/properties/name'))->toBeTrue()
        ->and($validator->validate('/items/0/properties/id'))->toBeTrue()
        ->and($validator->validate('/paths/~1users~1{id}/get'))->toBeTrue();
});

it('validates JSON pointer with multiple consecutive valid escapes', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/~0~0~0'))->toBeTrue()
        ->and($validator->validate('/~1~1~1'))->toBeTrue();
});

it('validates root level array access', function (): void {
    $validator = new JsonPointerFormatValidator();

    expect($validator->validate('/0'))->toBeTrue()
        ->and($validator->validate('/1'))->toBeTrue()
        ->and($validator->validate('/999'))->toBeTrue();
});
