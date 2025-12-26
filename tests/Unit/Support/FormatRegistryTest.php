<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Contracts\FormatValidatorInterface;
use Cline\JsonSchema\Support\FormatRegistry;

use function afterEach;
use function beforeEach;
use function describe;
use function expect;
use function is_string;
use function it;
use function preg_match;
use function str_starts_with;

describe('FormatRegistry', function (): void {
    beforeEach(function (): void {
        FormatRegistry::clear();
    });

    afterEach(function (): void {
        FormatRegistry::clear();
    });

    it('registers custom format validator', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return is_string($value) && str_starts_with($value, 'custom-');
            }

            public function format(): string
            {
                return 'custom-format';
            }
        };

        FormatRegistry::register('custom-format', $validator);

        expect(FormatRegistry::has('custom-format'))->toBeTrue();
    });

    it('retrieves registered format validator', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'test-format';
            }
        };

        FormatRegistry::register('test-format', $validator);
        $retrieved = FormatRegistry::get('test-format');

        expect($retrieved)->toBe($validator);
    });

    it('returns null for unregistered format', function (): void {
        expect(FormatRegistry::get('non-existent'))->toBeNull();
    });

    it('checks if format is registered', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'test';
            }
        };

        expect(FormatRegistry::has('test'))->toBeFalse();

        FormatRegistry::register('test', $validator);

        expect(FormatRegistry::has('test'))->toBeTrue();
    });

    it('unregisters format validator', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'test';
            }
        };

        FormatRegistry::register('test', $validator);
        expect(FormatRegistry::has('test'))->toBeTrue();

        FormatRegistry::unregister('test');
        expect(FormatRegistry::has('test'))->toBeFalse();
    });

    it('clears all format validators', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'format';
            }
        };

        FormatRegistry::register('format1', $validator);
        FormatRegistry::register('format2', $validator);

        expect(FormatRegistry::has('format1'))->toBeTrue()
            ->and(FormatRegistry::has('format2'))->toBeTrue();

        FormatRegistry::clear();

        expect(FormatRegistry::has('format1'))->toBeFalse()
            ->and(FormatRegistry::has('format2'))->toBeFalse();
    });

    it('overwrites existing format validator', function (): void {
        $validator1 = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'test';
            }
        };

        $validator2 = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return false;
            }

            public function format(): string
            {
                return 'test';
            }
        };

        FormatRegistry::register('test', $validator1);
        FormatRegistry::register('test', $validator2);

        expect(FormatRegistry::get('test'))->toBe($validator2);
    });

    it('returns registered format names', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return true;
            }

            public function format(): string
            {
                return 'format';
            }
        };

        FormatRegistry::register('format1', $validator);
        FormatRegistry::register('format2', $validator);

        $formats = FormatRegistry::getRegisteredFormats();

        expect($formats)->toBeArray()
            ->and($formats)->toContain('format1')
            ->and($formats)->toContain('format2');
    });

    it('handles multiple different validators', function (): void {
        $creditCard = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return is_string($value) && preg_match('/^\d{16}$/', $value) === 1;
            }

            public function format(): string
            {
                return 'credit-card';
            }
        };

        $phone = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return is_string($value) && preg_match('/^\d{3}-\d{3}-\d{4}$/', $value) === 1;
            }

            public function format(): string
            {
                return 'phone';
            }
        };

        FormatRegistry::register('credit-card', $creditCard);
        FormatRegistry::register('phone', $phone);

        expect(FormatRegistry::has('credit-card'))->toBeTrue()
            ->and(FormatRegistry::has('phone'))->toBeTrue()
            ->and(FormatRegistry::get('credit-card'))->toBe($creditCard)
            ->and(FormatRegistry::get('phone'))->toBe($phone);
    });

    it('custom validator is used in validation', function (): void {
        $validator = new class() implements FormatValidatorInterface
        {
            public function validate(mixed $value): bool
            {
                return is_string($value) && str_starts_with($value, 'TEST-');
            }

            public function format(): string
            {
                return 'test-prefix';
            }
        };

        FormatRegistry::register('test-prefix', $validator);

        $retrieved = FormatRegistry::get('test-prefix');
        expect($retrieved->validate('TEST-123'))->toBeTrue()
            ->and($retrieved->validate('ABC-123'))->toBeFalse();
    });
});
