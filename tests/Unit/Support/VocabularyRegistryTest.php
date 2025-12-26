<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Support\VocabularyRegistry;

use function expect;
use function it;

// ============================================================================
// isKeywordAllowed() - Backward Compatibility Tests
// ============================================================================

it('allows all keywords when no vocabularies are specified', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = [];

    // Act & Assert - should allow any keyword
    expect($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('customKeyword', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('unknownKeyword', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Core Vocabulary Tests
// ============================================================================

it('allows core keywords from Draft 2019-09 core vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/core'];

    // Act & Assert
    expect($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('id', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$schema', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$ref', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$anchor', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$recursiveRef', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$recursiveAnchor', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$vocabulary', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$comment', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$defs', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('definitions', $activeVocabularies))->toBeTrue();
});

it('rejects non-core keywords when only Draft 2019-09 core vocabulary is active', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/core'];

    // Act & Assert - applicator keywords should be rejected
    expect($registry->isKeywordAllowed('type', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('items', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('format', $activeVocabularies))->toBeFalse();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Applicator Vocabulary Tests
// ============================================================================

it('allows applicator keywords from Draft 2019-09 applicator vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/applicator'];

    // Act & Assert
    expect($registry->isKeywordAllowed('allOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('anyOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('oneOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('not', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('if', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('then', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('else', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('dependentSchemas', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('patternProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('additionalProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('propertyNames', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('items', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('additionalItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('unevaluatedItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('unevaluatedProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contains', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('prefixItems', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Validation Vocabulary Tests
// ============================================================================

it('allows validation keywords from Draft 2019-09 validation vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/validation'];

    // Act & Assert
    expect($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('enum', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('const', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('multipleOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('maximum', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('minimum', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('exclusiveMaximum', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('exclusiveMinimum', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('maxLength', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('minLength', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('pattern', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('maxItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('minItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('uniqueItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('maxContains', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('minContains', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('maxProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('minProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('required', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('dependentRequired', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Meta-data Vocabulary Tests
// ============================================================================

it('allows meta-data keywords from Draft 2019-09 meta-data vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/meta-data'];

    // Act & Assert
    expect($registry->isKeywordAllowed('title', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('description', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('default', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('deprecated', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('readOnly', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('writeOnly', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('examples', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Format Vocabulary Tests
// ============================================================================

it('allows format keyword from Draft 2019-09 format vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/format'];

    // Act & Assert
    expect($registry->isKeywordAllowed('format', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2019-09 Content Vocabulary Tests
// ============================================================================

it('allows content keywords from Draft 2019-09 content vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2019-09/vocab/content'];

    // Act & Assert
    expect($registry->isKeywordAllowed('contentEncoding', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contentMediaType', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contentSchema', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2020-12 Core Vocabulary Tests
// ============================================================================

it('allows core keywords from Draft 2020-12 core vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/core'];

    // Act & Assert
    expect($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('id', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$schema', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$ref', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$anchor', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$dynamicRef', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$dynamicAnchor', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$vocabulary', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$comment', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$defs', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('definitions', $activeVocabularies))->toBeTrue();
});

it('correctly differentiates Draft 2019-09 and Draft 2020-12 core keywords', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $draft2019Vocabularies = ['https://json-schema.org/draft/2019-09/vocab/core'];
    $draft2020Vocabularies = ['https://json-schema.org/draft/2020-12/vocab/core'];

    // Act & Assert - $recursiveRef is only in 2019-09
    expect($registry->isKeywordAllowed('$recursiveRef', $draft2019Vocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('$recursiveRef', $draft2020Vocabularies))->toBeFalse();

    // $dynamicRef is only in 2020-12
    expect($registry->isKeywordAllowed('$dynamicRef', $draft2019Vocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('$dynamicRef', $draft2020Vocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Draft 2020-12 Applicator Vocabulary Tests
// ============================================================================

it('allows applicator keywords from Draft 2020-12 applicator vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/applicator'];

    // Act & Assert
    expect($registry->isKeywordAllowed('allOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('anyOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('oneOf', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('not', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('if', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('then', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('else', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('dependentSchemas', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('patternProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('additionalProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('propertyNames', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('items', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('prefixItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('unevaluatedItems', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('unevaluatedProperties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contains', $activeVocabularies))->toBeTrue();
});

it('correctly differentiates Draft 2019-09 and Draft 2020-12 applicator keywords', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $draft2019Vocabularies = ['https://json-schema.org/draft/2019-09/vocab/applicator'];
    $draft2020Vocabularies = ['https://json-schema.org/draft/2020-12/vocab/applicator'];

    // Act & Assert - additionalItems is only in 2019-09
    expect($registry->isKeywordAllowed('additionalItems', $draft2019Vocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('additionalItems', $draft2020Vocabularies))->toBeFalse();
});

// ============================================================================
// isKeywordAllowed() - Draft 2020-12 Format Vocabularies Tests
// ============================================================================

it('allows format keyword from Draft 2020-12 format-annotation vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/format-annotation'];

    // Act & Assert
    expect($registry->isKeywordAllowed('format', $activeVocabularies))->toBeTrue();
});

it('allows format keyword from Draft 2020-12 format-assertion vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/format-assertion'];

    // Act & Assert
    expect($registry->isKeywordAllowed('format', $activeVocabularies))->toBeTrue();
});

// ============================================================================
// isKeywordAllowed() - Multiple Vocabularies Tests
// ============================================================================

it('allows keywords from multiple active vocabularies', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = [
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://json-schema.org/draft/2020-12/vocab/validation',
        'https://json-schema.org/draft/2020-12/vocab/applicator',
    ];

    // Act & Assert - should allow keywords from all three vocabularies
    expect($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeTrue() // core
        ->and($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue() // validation
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue() // applicator
        ->and($registry->isKeywordAllowed('format', $activeVocabularies))->toBeFalse(); // not in any active vocabulary
});

it('rejects keywords not in any active vocabulary', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = [
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://json-schema.org/draft/2020-12/vocab/validation',
    ];

    // Act & Assert - applicator keywords should be rejected
    expect($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('items', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('allOf', $activeVocabularies))->toBeFalse();
});

// ============================================================================
// isKeywordAllowed() - Unknown Vocabulary Tests
// ============================================================================

it('rejects keywords when vocabulary is unknown', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://example.com/custom-vocab'];

    // Act & Assert - should reject all keywords since vocabulary is unknown
    expect($registry->isKeywordAllowed('type', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('customKeyword', $activeVocabularies))->toBeFalse();
});

it('handles mix of known and unknown vocabularies', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = [
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://example.com/unknown-vocab',
    ];

    // Act & Assert - should allow keywords from known vocab, ignore unknown
    expect($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('type', $activeVocabularies))->toBeFalse();
});

// ============================================================================
// isKeywordAllowed() - Edge Cases
// ============================================================================

it('handles empty keyword string', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/core'];

    // Act & Assert
    expect($registry->isKeywordAllowed('', $activeVocabularies))->toBeFalse();
});

it('is case-sensitive for keyword matching', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/validation'];

    // Act & Assert - keywords are case-sensitive
    expect($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('Type', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed('TYPE', $activeVocabularies))->toBeFalse();
});

it('handles whitespace in keyword', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $activeVocabularies = ['https://json-schema.org/draft/2020-12/vocab/validation'];

    // Act & Assert - whitespace matters
    expect($registry->isKeywordAllowed('type ', $activeVocabularies))->toBeFalse()
        ->and($registry->isKeywordAllowed(' type', $activeVocabularies))->toBeFalse();
});

// ============================================================================
// getActiveVocabularies() - Happy Path Tests
// ============================================================================

it('extracts required vocabularies from metaschema', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/validation' => true,
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://json-schema.org/draft/2020-12/vocab/validation',
    ]);
});

it('excludes optional vocabularies from active list', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/validation' => true,
            'https://json-schema.org/draft/2020-12/vocab/format-annotation' => false,
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://json-schema.org/draft/2020-12/vocab/validation',
    ])
        ->and($result)->not->toContain('https://json-schema.org/draft/2020-12/vocab/format-annotation');
});

it('returns empty array when no vocabulary declaration exists', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$id' => 'https://example.com/schema',
        'type' => 'object',
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

it('returns empty array when vocabulary declaration is not an array', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => 'https://json-schema.org/draft/2020-12/vocab/core',
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

it('returns empty array when vocabulary declaration is null', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => null,
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

// ============================================================================
// getActiveVocabularies() - Edge Cases
// ============================================================================

it('returns empty array for empty vocabulary object', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

it('handles vocabulary with all optional entries', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => false,
            'https://json-schema.org/draft/2020-12/vocab/validation' => false,
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

it('only includes vocabularies with exactly true value', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/validation' => 1, // truthy but not true
            'https://json-schema.org/draft/2020-12/vocab/applicator' => 'true', // string
            'https://json-schema.org/draft/2020-12/vocab/meta-data' => [], // empty array (truthy)
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert - only strict true values
    expect($result)->toBe([
        'https://json-schema.org/draft/2020-12/vocab/core',
    ]);
});

it('handles empty metaschema', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert
    expect($result)->toBe([]);
});

it('preserves vocabulary URI order from metaschema', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/validation' => true,
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/applicator' => true,
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert - order should match insertion order
    expect($result)->toBe([
        'https://json-schema.org/draft/2020-12/vocab/validation',
        'https://json-schema.org/draft/2020-12/vocab/core',
        'https://json-schema.org/draft/2020-12/vocab/applicator',
    ]);
});

it('handles custom/unknown vocabulary URIs', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://example.com/custom-vocab' => true,
            'https://another-example.com/vocab' => true,
        ],
    ];

    // Act
    $result = $registry->getActiveVocabularies($metaschema);

    // Assert - should include custom URIs
    expect($result)->toBe([
        'https://example.com/custom-vocab',
        'https://another-example.com/vocab',
    ]);
});

// ============================================================================
// Integration Tests - Full Workflow
// ============================================================================

it('supports full vocabulary workflow from metaschema to keyword validation', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/validation' => true,
            'https://json-schema.org/draft/2020-12/vocab/applicator' => false, // optional
        ],
    ];

    // Act
    $activeVocabularies = $registry->getActiveVocabularies($metaschema);

    // Assert - can validate keywords against extracted vocabularies
    expect($registry->isKeywordAllowed('$id', $activeVocabularies))->toBeTrue() // core
        ->and($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue() // validation
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeFalse(); // applicator was optional
});

it('supports Draft 2019-09 complete vocabulary set', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2019-09/vocab/core' => true,
            'https://json-schema.org/draft/2019-09/vocab/applicator' => true,
            'https://json-schema.org/draft/2019-09/vocab/validation' => true,
            'https://json-schema.org/draft/2019-09/vocab/meta-data' => true,
            'https://json-schema.org/draft/2019-09/vocab/format' => true,
            'https://json-schema.org/draft/2019-09/vocab/content' => true,
        ],
    ];

    // Act
    $activeVocabularies = $registry->getActiveVocabularies($metaschema);

    // Assert - all Draft 2019-09 keywords should be available
    expect($registry->isKeywordAllowed('$recursiveRef', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('title', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('format', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contentEncoding', $activeVocabularies))->toBeTrue();
});

it('supports Draft 2020-12 complete vocabulary set', function (): void {
    // Arrange
    $registry = new VocabularyRegistry();
    $metaschema = [
        '$vocabulary' => [
            'https://json-schema.org/draft/2020-12/vocab/core' => true,
            'https://json-schema.org/draft/2020-12/vocab/applicator' => true,
            'https://json-schema.org/draft/2020-12/vocab/validation' => true,
            'https://json-schema.org/draft/2020-12/vocab/meta-data' => true,
            'https://json-schema.org/draft/2020-12/vocab/format-annotation' => true,
            'https://json-schema.org/draft/2020-12/vocab/content' => true,
        ],
    ];

    // Act
    $activeVocabularies = $registry->getActiveVocabularies($metaschema);

    // Assert - all Draft 2020-12 keywords should be available
    expect($registry->isKeywordAllowed('$dynamicRef', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('properties', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('type', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('title', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('format', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('contentEncoding', $activeVocabularies))->toBeTrue()
        ->and($registry->isKeywordAllowed('additionalItems', $activeVocabularies))->toBeFalse(); // removed in 2020-12
});
