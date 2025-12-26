<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function in_array;
use function is_array;

/**
 * Vocabulary registry for JSON Schema Draft 2019-09 and later.
 *
 * Maps vocabulary URIs to the keywords they define and provides validation
 * logic for keyword support. In Draft 2019-09+, schemas can declare which
 * vocabularies they require via the $vocabulary keyword, allowing for modular
 * extension of JSON Schema functionality.
 *
 * Vocabularies organize keywords by purpose: core (structural), applicator
 * (schema application), validation (constraints), meta-data (annotations),
 * format (string formats), and content (media types).
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.1.2 $vocabulary keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.1 Meta-schemas
 * @see https://json-schema.org/understanding-json-schema/reference/schema Meta-schemas and vocabularies
 * @see https://json-schema.org/specification#vocabulary-specifications Vocabulary specifications
 */
final class VocabularyRegistry
{
    /**
     * Mapping of vocabulary URIs to their defined keywords.
     *
     * Each vocabulary URI maps to an array of keywords that vocabulary provides.
     * Includes both Draft 2019-09 and Draft 2020-12 vocabularies.
     */
    private const array VOCABULARY_KEYWORDS = [
        'https://json-schema.org/draft/2019-09/vocab/core' => [
            '$id', 'id', '$schema', '$ref', '$anchor', '$recursiveRef', '$recursiveAnchor',
            '$vocabulary', '$comment', '$defs', 'definitions',
        ],
        'https://json-schema.org/draft/2019-09/vocab/applicator' => [
            'allOf', 'anyOf', 'oneOf', 'not', 'if', 'then', 'else',
            'dependentSchemas', 'properties', 'patternProperties', 'additionalProperties',
            'propertyNames', 'items', 'additionalItems', 'unevaluatedItems',
            'unevaluatedProperties', 'contains', 'prefixItems',
            'dependencies', // Deprecated in Draft 2019-09, but supported for backward compatibility
        ],
        'https://json-schema.org/draft/2019-09/vocab/validation' => [
            'type', 'enum', 'const', 'multipleOf', 'maximum', 'minimum',
            'exclusiveMaximum', 'exclusiveMinimum', 'maxLength', 'minLength', 'pattern',
            'maxItems', 'minItems', 'uniqueItems', 'maxContains', 'minContains',
            'maxProperties', 'minProperties', 'required', 'dependentRequired',
        ],
        'https://json-schema.org/draft/2019-09/vocab/meta-data' => [
            'title', 'description', 'default', 'deprecated', 'readOnly', 'writeOnly', 'examples',
        ],
        'https://json-schema.org/draft/2019-09/vocab/format' => [
            'format',
        ],
        'https://json-schema.org/draft/2019-09/vocab/content' => [
            'contentEncoding', 'contentMediaType', 'contentSchema',
        ],
        // Draft 2020-12 vocabularies
        'https://json-schema.org/draft/2020-12/vocab/core' => [
            '$id', 'id', '$schema', '$ref', '$anchor', '$dynamicRef', '$dynamicAnchor',
            '$vocabulary', '$comment', '$defs', 'definitions',
        ],
        'https://json-schema.org/draft/2020-12/vocab/applicator' => [
            'allOf', 'anyOf', 'oneOf', 'not', 'if', 'then', 'else',
            'dependentSchemas', 'properties', 'patternProperties', 'additionalProperties',
            'propertyNames', 'items', 'prefixItems', 'unevaluatedItems',
            'unevaluatedProperties', 'contains',
            'dependencies', // Deprecated in Draft 2019-09, but supported for backward compatibility
        ],
        'https://json-schema.org/draft/2020-12/vocab/validation' => [
            'type', 'enum', 'const', 'multipleOf', 'maximum', 'minimum',
            'exclusiveMaximum', 'exclusiveMinimum', 'maxLength', 'minLength', 'pattern',
            'maxItems', 'minItems', 'uniqueItems', 'maxContains', 'minContains',
            'maxProperties', 'minProperties', 'required', 'dependentRequired',
        ],
        'https://json-schema.org/draft/2020-12/vocab/meta-data' => [
            'title', 'description', 'default', 'deprecated', 'readOnly', 'writeOnly', 'examples',
        ],
        'https://json-schema.org/draft/2020-12/vocab/format-annotation' => [
            'format',
        ],
        'https://json-schema.org/draft/2020-12/vocab/format-assertion' => [
            'format',
        ],
        'https://json-schema.org/draft/2020-12/vocab/content' => [
            'contentEncoding', 'contentMediaType', 'contentSchema',
        ],
    ];

    /**
     * Check if a keyword is allowed in the given set of active vocabularies.
     *
     * Determines whether a specific keyword is supported by any of the currently
     * active vocabularies. If no vocabularies are specified (empty array), all
     * keywords are allowed for backward compatibility with pre-2019-09 drafts.
     *
     * @param string        $keyword            The keyword to check (e.g., 'type', 'properties')
     * @param array<string> $activeVocabularies URIs of active vocabularies from $vocabulary
     *
     * @return bool True if the keyword is allowed, false otherwise
     */
    public function isKeywordAllowed(string $keyword, array $activeVocabularies): bool
    {
        // If no vocabularies are specified, allow all keywords (backward compatibility)
        if ($activeVocabularies === []) {
            return true;
        }

        foreach ($activeVocabularies as $vocabularyUri) {
            if (!isset(self::VOCABULARY_KEYWORDS[$vocabularyUri])) {
                continue;
            }

            if (in_array($keyword, self::VOCABULARY_KEYWORDS[$vocabularyUri], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract active vocabularies from a metaschema's $vocabulary declaration.
     *
     * Parses the $vocabulary object from a metaschema and returns the URIs of
     * vocabularies that are required (have a value of true). Vocabularies with
     * a value of false are optional and not included in the returned array.
     *
     * @param array<string, mixed> $metaschema The metaschema containing $vocabulary declaration
     *
     * @return array<string> URIs of required vocabularies, or empty array if no $vocabulary
     */
    public function getActiveVocabularies(array $metaschema): array
    {
        if (!isset($metaschema['$vocabulary'])) {
            // No $vocabulary declaration - all vocabularies are implicitly active
            return [];
        }

        $vocabulary = $metaschema['$vocabulary'];

        if (!is_array($vocabulary)) {
            return [];
        }

        // Only include vocabularies that are required (value is true)
        $active = [];

        foreach ($vocabulary as $uri => $required) {
            if ($required !== true) {
                continue;
            }

            $active[] = $uri;
        }

        return $active;
    }
}
