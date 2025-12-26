<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Enums\OutputFormat;
use Cline\JsonSchema\Support\OutputFormatter;
use Cline\JsonSchema\ValueObjects\ValidationError;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function describe;
use function expect;
use function it;

describe('OutputFormatter', function (): void {
    it('formats flag output for valid result', function (): void {
        $result = ValidationResult::success();
        $output = OutputFormatter::format($result, OutputFormat::Flag);

        expect($output)->toBe(['valid' => true]);
    });

    it('formats flag output for invalid result', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/name', 'Required property missing', 'required'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Flag);

        expect($output)->toBe(['valid' => false]);
    });

    it('formats basic output for valid result', function (): void {
        $result = ValidationResult::success();
        $output = OutputFormatter::format($result, OutputFormat::Basic);

        expect($output)->toBe(['valid' => true])
            ->and($output)->not->toHaveKey('errors');
    });

    it('formats basic output for invalid result', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/name', 'Required property missing', 'required'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Basic);

        expect($output)->toHaveKey('valid')
            ->and($output['valid'])->toBeFalse()
            ->and($output)->toHaveKey('errors')
            ->and($output['errors'])->toHaveCount(1)
            ->and($output['errors'][0])->toHaveKeys(['instanceLocation', 'keywordLocation', 'error', 'keyword']);
    });

    it('formats basic output with multiple errors', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/name', 'Required property missing', 'required'),
            new ValidationError('/age', 'Must be a number', 'type'),
            new ValidationError('/email', 'Invalid email format', 'format'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Basic);

        expect($output['errors'])->toHaveCount(3);
    });

    it('formats detailed output for valid result', function (): void {
        $result = ValidationResult::success();
        $output = OutputFormatter::format($result, OutputFormat::Detailed);

        expect($output)->toBe(['valid' => true])
            ->and($output)->not->toHaveKey('errors');
    });

    it('formats detailed output for invalid result', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/user/name', 'Required property missing', 'required'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Detailed);

        expect($output)->toHaveKey('valid')
            ->and($output['valid'])->toBeFalse()
            ->and($output)->toHaveKey('errors');
    });

    it('formats verbose output for valid result', function (): void {
        $result = ValidationResult::success();
        $output = OutputFormatter::format($result, OutputFormat::Verbose);

        expect($output)->toBe(['valid' => true])
            ->and($output)->not->toHaveKey('errors');
    });

    it('formats verbose output for invalid result', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/name', 'Required property missing', 'required'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Verbose);

        expect($output)->toHaveKey('valid')
            ->and($output['valid'])->toBeFalse()
            ->and($output)->toHaveKey('errors')
            ->and($output['errors'][0])->toHaveKey('absoluteKeywordLocation');
    });

    it('includes all required fields in basic format', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/test', 'Test error', 'testKeyword'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Basic);

        expect($output['errors'][0])->toHaveKeys([
            'instanceLocation',
            'keywordLocation',
            'error',
            'keyword',
        ]);
    });

    it('includes absolute location in verbose format', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/test', 'Test error', 'testKeyword'),
        ]);
        $output = OutputFormatter::format($result, OutputFormat::Verbose);

        expect($output['errors'][0])->toHaveKey('absoluteKeywordLocation')
            ->and($output['errors'][0]['absoluteKeywordLocation'])->toContain('testKeyword');
    });

    it('ValidationResult format method uses Flag format', function (): void {
        $result = ValidationResult::success();
        $output = $result->format(OutputFormat::Flag);

        expect($output)->toBe(['valid' => true]);
    });

    it('ValidationResult format method uses Basic format', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/test', 'Error', 'keyword'),
        ]);
        $output = $result->format(OutputFormat::Basic);

        expect($output)->toHaveKeys(['valid', 'errors']);
    });

    it('ValidationResult format method uses Detailed format', function (): void {
        $result = ValidationResult::success();
        $output = $result->format(OutputFormat::Detailed);

        expect($output)->toHaveKey('valid');
    });

    it('ValidationResult format method uses Verbose format', function (): void {
        $result = ValidationResult::failure([
            new ValidationError('/test', 'Error', 'keyword'),
        ]);
        $output = $result->format(OutputFormat::Verbose);

        expect($output)->toHaveKeys(['valid', 'errors'])
            ->and($output['errors'][0])->toHaveKey('absoluteKeywordLocation');
    });
});
