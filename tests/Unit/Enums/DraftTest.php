<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Enums;

use Cline\JsonSchema\Enums\Draft;

use function expect;
use function it;

// ============================================================================
// Enum Cases Tests
// ============================================================================

it('has Draft04 case with correct value', function (): void {
    expect(Draft::Draft04)
        ->toBeInstanceOf(Draft::class)
        ->and(Draft::Draft04->value)->toBe('http://json-schema.org/draft-04/schema#');
});

it('has Draft06 case with correct value', function (): void {
    expect(Draft::Draft06)
        ->toBeInstanceOf(Draft::class)
        ->and(Draft::Draft06->value)->toBe('http://json-schema.org/draft-06/schema#');
});

it('has Draft07 case with correct value', function (): void {
    expect(Draft::Draft07)
        ->toBeInstanceOf(Draft::class)
        ->and(Draft::Draft07->value)->toBe('http://json-schema.org/draft-07/schema#');
});

it('has Draft201909 case with correct value', function (): void {
    expect(Draft::Draft201909)
        ->toBeInstanceOf(Draft::class)
        ->and(Draft::Draft201909->value)->toBe('https://json-schema.org/draft/2019-09/schema');
});

it('has Draft202012 case with correct value', function (): void {
    expect(Draft::Draft202012)
        ->toBeInstanceOf(Draft::class)
        ->and(Draft::Draft202012->value)->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('has exactly five draft cases', function (): void {
    expect(Draft::cases())->toHaveCount(5);
});

// ============================================================================
// fromSchemaUri() Method Tests - Happy Path
// ============================================================================

it('detects Draft04 from canonical URI', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-04/schema#');
    expect($result)->toBe(Draft::Draft04);
});

it('detects Draft06 from canonical URI', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-06/schema#');
    expect($result)->toBe(Draft::Draft06);
});

it('detects Draft07 from canonical URI', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-07/schema#');
    expect($result)->toBe(Draft::Draft07);
});

it('detects Draft201909 from canonical URI', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft/2019-09/schema');
    expect($result)->toBe(Draft::Draft201909);
});

it('detects Draft202012 from canonical URI', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft/2020-12/schema');
    expect($result)->toBe(Draft::Draft202012);
});

// ============================================================================
// fromSchemaUri() Method Tests - Partial Matches
// ============================================================================

it('detects Draft04 from partial match with extra path', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-04/schema#/definitions/positiveInteger');
    expect($result)->toBe(Draft::Draft04);
});

it('detects Draft06 from partial match with query string', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-06/schema?version=1');
    expect($result)->toBe(Draft::Draft06);
});

it('detects Draft07 from partial match with fragment', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-07/schema#definitions');
    expect($result)->toBe(Draft::Draft07);
});

it('detects Draft201909 from partial match', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft/2019-09/meta/core');
    expect($result)->toBe(Draft::Draft201909);
});

it('detects Draft202012 from partial match', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft/2020-12/meta/applicator');
    expect($result)->toBe(Draft::Draft202012);
});

// ============================================================================
// fromSchemaUri() Method Tests - Protocol Variations
// ============================================================================

it('detects Draft04 with https protocol', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft-04/schema#');
    expect($result)->toBe(Draft::Draft04);
});

it('detects Draft06 with https protocol', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft-06/schema#');
    expect($result)->toBe(Draft::Draft06);
});

it('detects Draft07 with https protocol', function (): void {
    $result = Draft::fromSchemaUri('https://json-schema.org/draft-07/schema#');
    expect($result)->toBe(Draft::Draft07);
});

it('detects Draft201909 with http protocol', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft/2019-09/schema');
    expect($result)->toBe(Draft::Draft201909);
});

it('detects Draft202012 with http protocol', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft/2020-12/schema');
    expect($result)->toBe(Draft::Draft202012);
});

// ============================================================================
// fromSchemaUri() Method Tests - Edge Cases
// ============================================================================

it('returns null for unknown schema URI', function (): void {
    $result = Draft::fromSchemaUri('http://example.com/schema');
    expect($result)->toBeNull();
});

it('returns null for empty string', function (): void {
    $result = Draft::fromSchemaUri('');
    expect($result)->toBeNull();
});

it('returns null for invalid draft version', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-05/schema#');
    expect($result)->toBeNull();
});

it('returns null for URI without draft keyword', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/schema#');
    expect($result)->toBeNull();
});

it('returns null for completely invalid URI', function (): void {
    $result = Draft::fromSchemaUri('not-a-uri');
    expect($result)->toBeNull();
});

it('detects draft from URI with mixed case (case sensitive)', function (): void {
    // str_contains is case-sensitive, so this should NOT match
    $result = Draft::fromSchemaUri('http://json-schema.org/DRAFT-04/schema#');
    expect($result)->toBeNull();
});

it('detects draft from URI with trailing slash', function (): void {
    $result = Draft::fromSchemaUri('http://json-schema.org/draft-07/schema#/');
    expect($result)->toBe(Draft::Draft07);
});

it('detects draft from URI with subdomain', function (): void {
    $result = Draft::fromSchemaUri('http://www.json-schema.org/draft-07/schema#');
    expect($result)->toBe(Draft::Draft07);
});

it('detects draft when version string appears in middle of URI', function (): void {
    $result = Draft::fromSchemaUri('http://example.com/draft-07/custom-schema');
    expect($result)->toBe(Draft::Draft07);
});

it('handles URI with multiple draft versions (returns first match)', function (): void {
    // Based on the match order, draft-04 is checked first
    $result = Draft::fromSchemaUri('http://example.com/draft-04/draft-06/schema');
    expect($result)->toBe(Draft::Draft04);
});

it('handles URI with year format in different context', function (): void {
    $result = Draft::fromSchemaUri('http://example.com/archive/2019-09-15/schema');
    expect($result)->toBe(Draft::Draft201909);
});

// ============================================================================
// label() Method Tests
// ============================================================================

it('returns correct label for Draft04', function (): void {
    expect(Draft::Draft04->label())->toBe('Draft 04');
});

it('returns correct label for Draft06', function (): void {
    expect(Draft::Draft06->label())->toBe('Draft 06');
});

it('returns correct label for Draft07', function (): void {
    expect(Draft::Draft07->label())->toBe('Draft 07');
});

it('returns correct label for Draft201909', function (): void {
    expect(Draft::Draft201909->label())->toBe('Draft 2019-09');
});

it('returns correct label for Draft202012', function (): void {
    expect(Draft::Draft202012->label())->toBe('Draft 2020-12');
});

// ============================================================================
// Label Format Consistency Tests
// ============================================================================

it('has consistent label format for numbered drafts', function (): void {
    $labels = [
        Draft::Draft04->label(),
        Draft::Draft06->label(),
        Draft::Draft07->label(),
    ];

    foreach ($labels as $label) {
        expect($label)->toStartWith('Draft ');
    }
});

it('has consistent label format for dated drafts', function (): void {
    $labels = [
        Draft::Draft201909->label(),
        Draft::Draft202012->label(),
    ];

    foreach ($labels as $label) {
        expect($label)
            ->toStartWith('Draft ')
            ->toMatch('/Draft \d{4}-\d{2}/');
    }
});

// ============================================================================
// Enum Value Consistency Tests
// ============================================================================

it('has http protocol for older drafts', function (): void {
    expect(Draft::Draft04->value)->toStartWith('http://')
        ->and(Draft::Draft06->value)->toStartWith('http://')
        ->and(Draft::Draft07->value)->toStartWith('http://');
});

it('has https protocol for newer drafts', function (): void {
    expect(Draft::Draft201909->value)->toStartWith('https://')
        ->and(Draft::Draft202012->value)->toStartWith('https://');
});

it('has schema fragment identifier for numbered drafts', function (): void {
    expect(Draft::Draft04->value)->toEndWith('#')
        ->and(Draft::Draft06->value)->toEndWith('#')
        ->and(Draft::Draft07->value)->toEndWith('#');
});

it('does not have schema fragment identifier for dated drafts', function (): void {
    expect(Draft::Draft201909->value)->not->toEndWith('#')
        ->and(Draft::Draft202012->value)->not->toEndWith('#');
});

// ============================================================================
// Round-trip Tests
// ============================================================================

it('can round-trip Draft04 through fromSchemaUri', function (): void {
    $draft = Draft::Draft04;
    $detected = Draft::fromSchemaUri($draft->value);
    expect($detected)->toBe($draft);
});

it('can round-trip Draft06 through fromSchemaUri', function (): void {
    $draft = Draft::Draft06;
    $detected = Draft::fromSchemaUri($draft->value);
    expect($detected)->toBe($draft);
});

it('can round-trip Draft07 through fromSchemaUri', function (): void {
    $draft = Draft::Draft07;
    $detected = Draft::fromSchemaUri($draft->value);
    expect($detected)->toBe($draft);
});

it('can round-trip Draft201909 through fromSchemaUri', function (): void {
    $draft = Draft::Draft201909;
    $detected = Draft::fromSchemaUri($draft->value);
    expect($detected)->toBe($draft);
});

it('can round-trip Draft202012 through fromSchemaUri', function (): void {
    $draft = Draft::Draft202012;
    $detected = Draft::fromSchemaUri($draft->value);
    expect($detected)->toBe($draft);
});

// ============================================================================
// Enum Comparison Tests
// ============================================================================

it('can compare enum instances with identity', function (): void {
    expect(Draft::Draft07 === Draft::Draft07)->toBeTrue()
        ->and(Draft::Draft07 === Draft::Draft06)->toBeFalse();
});

it('can compare enum instances with equals', function (): void {
    expect(Draft::Draft07 === Draft::Draft07)->toBeTrue()
        ->and(Draft::Draft07 === Draft::Draft06)->toBeFalse();
});

it('drafts are ordered chronologically by definition', function (): void {
    $cases = Draft::cases();
    expect($cases[0])->toBe(Draft::Draft04)
        ->and($cases[1])->toBe(Draft::Draft06)
        ->and($cases[2])->toBe(Draft::Draft07)
        ->and($cases[3])->toBe(Draft::Draft201909)
        ->and($cases[4])->toBe(Draft::Draft202012);
});

// ============================================================================
// String Casting and Serialization Tests
// ============================================================================

it('can get string value from enum', function (): void {
    expect(Draft::Draft07->value)->toBeString()
        ->and(Draft::Draft07->value)->toBe('http://json-schema.org/draft-07/schema#');
});

it('enum name matches case name', function (): void {
    expect(Draft::Draft04->name)->toBe('Draft04')
        ->and(Draft::Draft06->name)->toBe('Draft06')
        ->and(Draft::Draft07->name)->toBe('Draft07')
        ->and(Draft::Draft201909->name)->toBe('Draft201909')
        ->and(Draft::Draft202012->name)->toBe('Draft202012');
});

// ============================================================================
// Real-world URI Pattern Tests
// ============================================================================

it('detects draft from meta-schema URIs', function (): void {
    expect(Draft::fromSchemaUri('http://json-schema.org/draft-07/schema#'))->toBe(Draft::Draft07)
        ->and(Draft::fromSchemaUri('https://json-schema.org/draft/2020-12/meta/core'))->toBe(Draft::Draft202012)
        ->and(Draft::fromSchemaUri('https://json-schema.org/draft/2019-09/meta/applicator'))->toBe(Draft::Draft201909);
});

it('detects draft from schema references in documents', function (): void {
    // Simulates $schema values you might find in actual JSON Schema documents
    expect(Draft::fromSchemaUri('http://json-schema.org/draft-04/schema#'))->toBe(Draft::Draft04)
        ->and(Draft::fromSchemaUri('http://json-schema.org/draft-06/schema#'))->toBe(Draft::Draft06)
        ->and(Draft::fromSchemaUri('http://json-schema.org/draft-07/schema#'))->toBe(Draft::Draft07);
});

it('detects draft from OpenAPI specification schema references', function (): void {
    // OpenAPI 3.0 uses JSON Schema Draft 05 (which we don't support, should return null)
    expect(Draft::fromSchemaUri('http://json-schema.org/draft-05/schema#'))->toBeNull();

    // OpenAPI 3.1 uses JSON Schema Draft 2020-12
    expect(Draft::fromSchemaUri('https://json-schema.org/draft/2020-12/schema'))->toBe(Draft::Draft202012);
});
