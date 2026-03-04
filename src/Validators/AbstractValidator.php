<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators;

use Cline\JsonSchema\Contracts\ValidatorInterface;
use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Exceptions\ValidationDepthExceededException;
use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\Support\VocabularyRegistry;
use Cline\JsonSchema\ValueObjects\ValidationError;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function array_any;
use function array_merge;
use function array_pop;
use function array_unique;
use function assert;
use function end;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function lcfirst;
use function mb_rtrim;
use function mb_strpos;
use function mb_strrpos;
use function mb_substr;
use function method_exists;
use function parse_url;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function throw_if;

/**
 * Base validator implementing core JSON Schema validation logic.
 *
 * Provides the foundation for all draft-specific validators with comprehensive
 * validation orchestration, reference resolution, vocabulary support, and annotation
 * tracking for unevaluated properties/items.
 *
 * The validator implements a three-pass validation strategy:
 * 1. Non-composition keywords (properties, items, type, etc.)
 * 2. Composition keywords (allOf, anyOf, oneOf, if/then/else) with annotation isolation
 * 3. Unevaluated keywords (unevaluatedProperties, unevaluatedItems) seeing merged annotations
 *
 * Supports Draft 2019-09+ vocabulary-based keyword validation and maintains proper
 * schema scope tracking for $ref, $dynamicRef, and anchor resolution.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core Core specification (Draft 2020-12)
 * @see https://json-schema.org/draft/2020-12/json-schema-validation Validation specification
 * @see https://json-schema.org/understanding-json-schema Understanding JSON Schema guide
 * @see https://json-schema.org/understanding-json-schema/reference Schema keyword reference
 *
 * @method bool validateAllOf(mixed $data, array<string, mixed> $schema)
 * @method bool validateAnyOf(mixed $data, array<string, mixed> $schema)
 * @method bool validateIf(mixed $data, array<string, mixed> $schema)
 * @method bool validateNot(mixed $data, array<string, mixed> $schema)
 * @method bool validateOneOf(mixed $data, array<string, mixed> $schema)
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Collection of validation errors encountered during validation.
     *
     * @var array<ValidationError>
     */
    protected array $errors = [];

    /**
     * Current JSON path being validated (e.g., "$.user.address.street").
     */
    protected string $currentPath = '$';

    /**
     * The root schema document being validated against.
     *
     * @var array<string, mixed>
     */
    protected array $rootSchema = [];

    /**
     * Dynamic scope stack for $dynamicRef resolution (Draft 2020-12+).
     *
     * Maintains a stack of schema scopes with their anchors and base URIs
     * for proper dynamic reference resolution.
     *
     * @var array<array{schema: array<string, mixed>|bool, anchor: null|string, baseUri?: string}>
     */
    protected array $dynamicScope = [];

    /**
     * Tracks which object properties have been evaluated by validation keywords.
     *
     * Used for unevaluatedProperties implementation. Maps JSON paths to arrays
     * of property names that have been processed.
     *
     * @var array<string, array<string>>
     */
    protected array $evaluatedProperties = [];

    /**
     * Tracks which array items have been evaluated by validation keywords.
     *
     * Used for unevaluatedItems implementation. Maps JSON paths to arrays
     * of item indices that have been processed.
     *
     * @var array<string, array<int>>
     */
    protected array $evaluatedItems = [];

    /**
     * Stack of base URIs for resolving relative schema references.
     *
     * Updated as we traverse schemas with $id keywords. The current base URI
     * is always at the end of the stack.
     *
     * @var array<string>
     */
    protected array $baseUriStack = [];

    /**
     * Registry mapping schema URIs to their schema definitions.
     *
     * Built during schema initialization by recursively registering all schemas
     * with $id, $anchor, or $dynamicAnchor keywords.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $schemaRegistry = [];

    /**
     * Current validation depth counter for infinite recursion detection.
     *
     * Incremented on each recursive validateSchema call, throws exception if
     * depth exceeds 1000.
     */
    protected int $validationDepth = 0;

    /**
     * Stack of schema URIs currently being validated.
     *
     * Used for detecting circular reference patterns.
     *
     * @var array<string>
     */
    protected array $validatingSchemas = [];

    /**
     * Schema loader for resolving external schema references.
     */
    protected SchemaLoader $schemaLoader;

    /**
     * Vocabulary registry for Draft 2019-09+ keyword validation.
     */
    protected VocabularyRegistry $vocabularyRegistry;

    /**
     * Active vocabularies for the current validation based on metaschema.
     *
     * Extracted from the $schema's $vocabulary declaration. Empty array means
     * all keywords allowed (pre-2019-09 behavior).
     *
     * @var array<string>
     */
    protected array $activeVocabularies = [];

    /**
     * Full vocabulary declaration from the metaschema.
     *
     * Contains all vocabularies (both required:true and optional:false) from
     * the metaschema's $vocabulary object. Used for checking vocabulary presence
     * regardless of required status.
     *
     * @var array<string, bool>
     */
    protected array $metaschemaVocabularies = [];

    /**
     * Create a new validator instance.
     *
     * @param null|SchemaLoader $schemaLoader Optional schema loader for external references.
     *                                        Defaults to new SchemaLoader instance.
     */
    public function __construct(
        ?SchemaLoader $schemaLoader = null,
        /**
         * Whether to enable format validation.
         *
         * When true, format keywords will be validated. When false, format is annotation-only.
         * This is used to support the test suite's requirement that optional/format tests
         * should run with validation enabled, while required tests run with it disabled.
         */
        protected readonly bool $enableFormatValidation = false,
    ) {
        $this->schemaLoader = $schemaLoader ?? new SchemaLoader();
        $this->vocabularyRegistry = new VocabularyRegistry();
    }

    /**
     * Validate data against a JSON Schema.
     *
     * Initializes the validator state and performs comprehensive validation of the
     * provided data against the schema. Handles boolean schemas (true/false), builds
     * the schema registry for $id/$anchor resolution, extracts active vocabularies
     * from the metaschema, and orchestrates the validation process.
     *
     * @param mixed                     $data   The data to validate (any JSON-compatible value)
     * @param array<string, mixed>|bool $schema The schema definition (object schema or boolean)
     *
     * @return ValidationResult The validation result with success status and any errors
     */
    public function validate(mixed $data, mixed $schema): ValidationResult
    {
        // Handle boolean schemas early
        if ($schema === true) {
            return ValidationResult::success();
        }

        if ($schema === false) {
            return ValidationResult::failure([]);
        }

        $this->errors = [];
        $this->currentPath = '$';
        $this->rootSchema = $schema;
        $this->dynamicScope = [];
        $this->evaluatedProperties = [];
        $this->evaluatedItems = [];
        $this->baseUriStack = [];
        $this->schemaRegistry = [];
        $this->validationDepth = 0;
        $this->metaschemaVocabularies = [];

        // Initialize base URI from root schema's id
        $rootId = $schema['id'] ?? $schema['$id'] ?? '';
        $rootIdStr = is_string($rootId) ? $rootId : '';
        $this->baseUriStack[] = $rootIdStr;

        // Build schema registry
        $this->registerSchemas($schema, $rootIdStr);

        // Extract active vocabularies from metaschema (Draft 2019-09+)
        $this->activeVocabularies = [];

        if (isset($schema['$schema']) && is_string($schema['$schema'])) {
            $metaschemaUri = $schema['$schema'];
            $metaschema = $this->schemaLoader->load($metaschemaUri);

            if ($metaschema !== null) {
                $this->activeVocabularies = $this->vocabularyRegistry->getActiveVocabularies($metaschema);

                // Store full vocabulary declaration (both required and optional vocabularies)
                if (isset($metaschema['$vocabulary']) && is_array($metaschema['$vocabulary'])) {
                    /** @var array<string, bool> $vocabularies */
                    $vocabularies = $metaschema['$vocabulary'];
                    $this->metaschemaVocabularies = $vocabularies;
                }
            }
        }

        $valid = $this->validateSchema($data, $schema);

        return $valid && $this->errors === []
            ? ValidationResult::success()
            : ValidationResult::failure($this->errors);
    }

    /**
     * Get the JSON Schema draft version supported by this validator.
     *
     * Each concrete validator implementation supports a specific draft version
     * (e.g., Draft04, Draft07, Draft2020_12).
     *
     * @return Draft The supported draft version enum
     */
    abstract public function supportedDraft(): Draft;

    /**
     * Validate data against a schema (internal recursive method).
     *
     * Core validation logic that processes schemas recursively. Implements the
     * three-pass validation strategy: non-composition keywords, composition keywords
     * with annotation isolation, and unevaluated keywords seeing merged annotations.
     *
     * Handles boolean schemas, tracks validation depth for recursion detection,
     * manages dynamic scope for $dynamicRef, processes $ref overrides in older drafts,
     * and maintains base URI stack for reference resolution.
     *
     * @param mixed                     $data   The data to validate
     * @param array<string, mixed>|bool $schema The schema definition (array or boolean)
     *
     * @return bool True if validation passes, false otherwise
     */
    protected function validateSchema(mixed $data, mixed $schema): bool
    {
        // Boolean schemas: true accepts everything, false rejects everything
        if ($schema === true) {
            return true;
        }

        if ($schema === false) {
            return false;
        }

        // Detect infinite recursion
        ++$this->validationDepth;

        throw_if($this->validationDepth > 1_000, ValidationDepthExceededException::maximumDepthReached(1_000));

        // Handle id keyword to update base URI FIRST (before pushing to dynamic scope)
        // This ensures dynamic scope entries have the correct base URI
        $pushedBaseUri = false;
        $currentBase = end($this->baseUriStack) ?: '';

        // Only process $id if $ref doesn't override it (Draft 04-06)
        if ((!isset($schema['$ref']) || !$this->refOverridesSiblings()) && (isset($schema['id']) || isset($schema['$id']))) {
            $id = $schema['id'] ?? $schema['$id'];

            if (is_string($id) && $id !== '') {
                $newBase = $this->resolveUri($currentBase, $id);
                $this->baseUriStack[] = $newBase;
                $currentBase = $newBase; // Update for dynamic scope
                $pushedBaseUri = true;
            }
        }

        // Push schema onto dynamic scope stack with its base URI
        $anchor = $schema['$dynamicAnchor'] ?? null;
        $anchorStr = is_string($anchor) ? $anchor : null;
        $this->dynamicScope[] = ['schema' => $schema, 'anchor' => $anchorStr, 'baseUri' => $currentBase];

        $valid = true;

        // In Draft 04-06, $ref overrides all sibling keywords (including id)
        // Check if we should skip other keywords when $ref is present
        if (isset($schema['$ref']) && $this->refOverridesSiblings()) {
            // Only validate $ref, ignore all other keywords (including id)
            if (method_exists($this, 'validateRef')) {
                /** @var bool $valid */
                $valid = $this->validateRef($data, $schema);
            }

            array_pop($this->dynamicScope);
            --$this->validationDepth;

            if ($pushedBaseUri) {
                array_pop($this->baseUriStack);
            }

            return $valid;
        }

        // Validate keywords in three passes to ensure proper annotation isolation:
        // 1. Non-composition keywords (properties, items, etc.)
        // 2. Composition keywords (allOf, anyOf, oneOf) - each sees only pass 1 annotations
        // 3. Unevaluated keywords - see merged annotations from passes 1 and 2

        $nonCompositionKeywords = ['Ref', 'RecursiveRef', 'DynamicRef', 'Type', 'MinLength', 'MaxLength', 'Pattern', 'Format',
            'Minimum', 'Maximum', 'ExclusiveMinimum', 'ExclusiveMaximum', 'MultipleOf', 'Required',
            'MinProperties', 'MaxProperties', 'Properties', 'AdditionalProperties', 'Dependencies', 'PatternProperties',
            'PropertyNames', 'DependentRequired', 'DependentSchemas',
            'Items', 'PrefixItems', 'AdditionalItems', 'Contains',
            'MinItems', 'MaxItems', 'UniqueItems',
            'Enum', 'Const', 'ContentEncoding', 'ContentMediaType'];
        $compositionKeywords = ['AllOf', 'AnyOf', 'OneOf', 'If', 'Not'];
        $unevaluatedKeywords = ['UnevaluatedProperties', 'UnevaluatedItems'];

        $valid = true;

        // Pass 1: Non-composition keywords
        foreach ($nonCompositionKeywords as $keyword) {
            // Check if keyword is allowed based on active vocabularies
            // Note: Format is handled specially - if either format-annotation or format-assertion
            // vocabulary is active, the keyword is allowed. The validateFormat method determines
            // whether to actually validate or treat as annotation-only.
            if (!$this->isKeywordAllowed($keyword)) {
                continue;
            }

            $method = 'validate'.$keyword;

            if (!method_exists($this, $method)) {
                continue;
            }

            if ($this->{$method}($data, $schema)) {
                continue;
            }

            $valid = false;
        }

        // Pass 2: Composition keywords (isolated from each other)
        // Check if any composition keywords exist in the schema
        $hasCompositionKeywords = false;

        foreach ($compositionKeywords as $keyword) {
            if ($this->isKeywordAllowed($keyword) && isset($schema[lcfirst($keyword)])) {
                $hasCompositionKeywords = true;

                break;
            }
        }

        if ($hasCompositionKeywords) {
            $stateBeforeComposition = $this->saveEvaluationState();
            $compositionStates = [];

            foreach ($compositionKeywords as $keyword) {
                // Check if keyword is allowed based on active vocabularies
                if (!$this->isKeywordAllowed($keyword)) {
                    continue;
                }

                if (!isset($schema[lcfirst($keyword)])) {
                    continue;
                }

                $method = 'validate'.$keyword;

                if (!method_exists($this, $method)) {
                    continue;
                }

                // Restore to pre-composition state (isolate from sibling composition keywords)
                $this->restoreEvaluationState($stateBeforeComposition);

                if (!$this->{$method}($data, $schema)) {
                    $valid = false;
                }

                // Save this composition keyword's annotations
                $compositionStates[] = $this->saveEvaluationState();
            }

            // Merge all composition annotations
            $this->restoreEvaluationState($stateBeforeComposition);

            foreach ($compositionStates as $state) {
                $this->mergeEvaluationState($state);
            }
        }

        // Pass 3: Unevaluated keywords (see merged annotations)
        foreach ($unevaluatedKeywords as $keyword) {
            // Check if keyword is allowed based on active vocabularies
            if (!$this->isKeywordAllowed($keyword)) {
                continue;
            }

            $method = 'validate'.$keyword;

            if (!method_exists($this, $method)) {
                continue;
            }

            if ($this->{$method}($data, $schema)) {
                continue;
            }

            $valid = false;
        }

        // Pop base URI if we pushed one
        if ($pushedBaseUri) {
            array_pop($this->baseUriStack);
        }

        // Pop schema from dynamic scope stack
        array_pop($this->dynamicScope);

        // Decrement validation depth
        --$this->validationDepth;

        return $valid;
    }

    /**
     * Determine if $ref should override sibling keywords.
     *
     * In Draft 04-06, $ref overrides all sibling keywords and only the reference
     * is processed. In Draft 07+, $ref coexists with siblings and all keywords
     * are validated together.
     *
     * Concrete validators override this for draft-specific behavior.
     *
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3.1 $ref adjacent keywords
     *
     * @return bool True if $ref overrides siblings (Draft 04-06), false otherwise (Draft 07+)
     */
    protected function refOverridesSiblings(): bool
    {
        // Default behavior (Draft 07+): $ref does not override siblings
        return false;
    }

    /**
     * Add a validation error to the errors collection.
     *
     * @param string $keyword The failing keyword (e.g., 'type', 'minLength')
     * @param string $message Human-readable error message
     */
    protected function addError(string $keyword, string $message): void
    {
        $this->errors[] = new ValidationError($this->currentPath, $message, $keyword);
    }

    /**
     * Mark an object property as evaluated.
     *
     * @param string $path     The JSON path to the object
     * @param string $property The property name
     */
    protected function markPropertyEvaluated(string $path, string $property): void
    {
        if (!isset($this->evaluatedProperties[$path])) {
            $this->evaluatedProperties[$path] = [];
        }

        $this->evaluatedProperties[$path][] = $property;
    }

    /**
     * Mark an array item as evaluated.
     *
     * @param string $path  The JSON path to the array
     * @param int    $index The item index
     */
    protected function markItemEvaluated(string $path, int $index): void
    {
        if (!isset($this->evaluatedItems[$path])) {
            $this->evaluatedItems[$path] = [];
        }

        $this->evaluatedItems[$path][] = $index;
    }

    /**
     * Get evaluated properties for a path.
     *
     * @param string $path The JSON path
     *
     * @return array<string> Array of evaluated property names
     */
    protected function getEvaluatedProperties(string $path): array
    {
        return $this->evaluatedProperties[$path] ?? [];
    }

    /**
     * Get evaluated items for a path.
     *
     * @param string $path The JSON path
     *
     * @return array<int> Array of evaluated item indices
     */
    protected function getEvaluatedItems(string $path): array
    {
        return $this->evaluatedItems[$path] ?? [];
    }

    /**
     * Save current evaluation state (properties and items).
     *
     * @return array{properties: array<string, array<string>>, items: array<string, array<int>>, errors: array<ValidationError>}
     */
    protected function saveEvaluationState(): array
    {
        return [
            'properties' => $this->evaluatedProperties,
            'items' => $this->evaluatedItems,
            'errors' => $this->errors,
        ];
    }

    /**
     * Restore evaluation state to a previous snapshot.
     *
     * @param array{properties: array<string, array<string>>, items: array<string, array<int>>, errors?: array<ValidationError>} $state
     */
    protected function restoreEvaluationState(array $state): void
    {
        $this->evaluatedProperties = $state['properties'];
        $this->evaluatedItems = $state['items'];
        $this->errors = $state['errors'] ?? [];
    }

    /**
     * Check if a keyword is allowed based on active vocabularies.
     *
     * Converts PascalCase method names (e.g., 'MinLength') to schema keywords (e.g., 'minLength').
     *
     * @param string $methodKeyword The keyword in PascalCase (e.g., 'MinLength')
     */
    protected function isKeywordAllowed(string $methodKeyword): bool
    {
        // If no active vocabularies, allow all keywords (backward compatibility)
        if ($this->activeVocabularies === []) {
            return true;
        }

        // Convert PascalCase to camelCase (e.g., 'MinLength' -> 'minLength')
        $schemaKeyword = lcfirst($methodKeyword);

        // Special cases for keywords with different names
        $keywordMap = [
            'Ref' => '$ref',
            'RecursiveRef' => '$recursiveRef',
            'DynamicRef' => '$dynamicRef',
        ];

        $schemaKeyword = $keywordMap[$methodKeyword] ?? $schemaKeyword;

        // Special handling for 'format' keyword: it can be in either format-annotation
        // or format-assertion vocabulary (2020-12) or format vocabulary (2019-09).
        // Check metaschemaVocabularies for presence regardless of required status (true/false).
        if ($schemaKeyword === 'format' && $this->metaschemaVocabularies !== []) {
            $format201909Uri = 'https://json-schema.org/draft/2019-09/vocab/format';
            $formatAnnotationUri = 'https://json-schema.org/draft/2020-12/vocab/format-annotation';
            $formatAssertionUri = 'https://json-schema.org/draft/2020-12/vocab/format-assertion';

            return isset($this->metaschemaVocabularies[$format201909Uri])
                || isset($this->metaschemaVocabularies[$formatAnnotationUri])
                || isset($this->metaschemaVocabularies[$formatAssertionUri]);
        }

        return $this->vocabularyRegistry->isKeywordAllowed($schemaKeyword, $this->activeVocabularies);
    }

    /**
     * Merge evaluation state from a snapshot into current state.
     *
     * @param array{properties: array<string, array<string>>, items: array<string, array<int>>, errors?: array<ValidationError>} $state
     */
    protected function mergeEvaluationState(array $state): void
    {
        foreach ($state['properties'] as $path => $properties) {
            if (!isset($this->evaluatedProperties[$path])) {
                $this->evaluatedProperties[$path] = [];
            }

            $this->evaluatedProperties[$path] = array_unique(array_merge(
                $this->evaluatedProperties[$path],
                $properties,
            ));
        }

        foreach ($state['items'] as $path => $items) {
            if (!isset($this->evaluatedItems[$path])) {
                $this->evaluatedItems[$path] = [];
            }

            $this->evaluatedItems[$path] = array_unique(array_merge(
                $this->evaluatedItems[$path],
                $items,
            ));
        }

        // Merge errors from composition keywords
        if (!isset($state['errors'])) {
            return;
        }

        $this->errors = array_merge($this->errors, $state['errors']);
    }

    /**
     * Get the current base URI for resolving relative references.
     *
     * @return string The current base URI
     */
    protected function getCurrentBaseUri(): string
    {
        return end($this->baseUriStack) ?: '';
    }

    /**
     * Resolve a URI against a base URI per RFC 3986.
     *
     * Implements URI reference resolution for handling relative schema URIs.
     * Absolute URIs (with scheme) are returned as-is. Relative URIs are resolved
     * against the base URI following RFC 3986 rules.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3986#section-5 URI resolution (RFC 3986)
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-9.1 Base URI and dereferencing
     *
     * @param string $base The base URI (e.g., "https://example.com/schemas/main.json")
     * @param string $ref  The reference URI (can be relative or absolute)
     *
     * @return string The resolved absolute URI
     */
    protected function resolveUri(string $base, string $ref): string
    {
        // If ref is absolute (has scheme), use it as-is
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $ref)) {
            return $ref;
        }

        // If base is empty, return ref as-is
        if ($base === '') {
            return $ref;
        }

        // Parse base URI
        $baseParts = parse_url($base);

        if ($baseParts === false) {
            return $ref;
        }

        // Handle fragment-only references
        if ($ref === '' || $ref[0] === '#') {
            return mb_rtrim($base, '#').$ref;
        }

        // Handle absolute path references
        if ($ref[0] === '/') {
            $scheme = $baseParts['scheme'] ?? '';
            $host = $baseParts['host'] ?? '';
            $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';

            return sprintf('%s://%s%s%s', $scheme, $host, $port, $ref);
        }

        // Handle relative path references
        $basePath = $baseParts['path'] ?? '/';
        $baseDir = mb_substr($basePath, 0, (int) mb_strrpos($basePath, '/') + 1);

        $scheme = $baseParts['scheme'] ?? '';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';

        // Combine and normalize path
        $fullPath = $baseDir.$ref;
        $fullPath = $this->normalizePath($fullPath);

        return sprintf('%s://%s%s%s', $scheme, $host, $port, $fullPath);
    }

    /**
     * Register schemas in the schema registry by their $id, $anchor, and $dynamicAnchor.
     *
     * Recursively walks the schema tree and registers each subschema that has an
     * identifying keyword ($id, $anchor, or $dynamicAnchor). This builds a lookup
     * table for resolving $ref and $dynamicRef references by URI.
     *
     * The registry enables O(1) lookupfor schema resolution instead of tree traversal.
     *
     * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.2 Schema anchors
     *
     * @param array<string, mixed> $schema  The schema to register and traverse
     * @param string               $baseUri The current base URI for resolving relative $id values
     */
    protected function registerSchemas(array $schema, string $baseUri): void
    {
        // Register this schema if it has an id
        if (isset($schema['id']) || isset($schema['$id'])) {
            $id = $schema['id'] ?? $schema['$id'];

            if (is_string($id) && $id !== '') {
                $uri = $this->resolveUri($baseUri, $id);
                $this->schemaRegistry[$uri] = $schema;
                $baseUri = $uri; // Update base for nested schemas
            }
        }

        // Register this schema if it has an $anchor (Draft 2019-09+)
        if (isset($schema['$anchor'])) {
            $anchor = $schema['$anchor'];

            if (is_string($anchor) && $anchor !== '') {
                // Anchors are registered with the current base URI
                $anchorUri = mb_rtrim($baseUri, '#').'#'.$anchor;
                $this->schemaRegistry[$anchorUri] = $schema;
            }
        }

        // Register this schema if it has a $dynamicAnchor (Draft 2020-12+)
        // $dynamicAnchor creates both a dynamic and static anchor
        if (isset($schema['$dynamicAnchor'])) {
            $anchor = $schema['$dynamicAnchor'];

            if (is_string($anchor) && $anchor !== '') {
                // Register as a static anchor too
                $anchorUri = mb_rtrim($baseUri, '#').'#'.$anchor;
                $this->schemaRegistry[$anchorUri] = $schema;
            }
        }

        // Recursively register nested schemas
        foreach ($schema as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            // Skip keywords where values are data, not schemas
            // enum and const contain literal data values that may happen to have schema-like structure
            if ($key === 'enum') {
                continue;
            }

            if ($key === 'const') {
                continue;
            }

            // Check if it's an array of schemas (sequential array)
            if (isset($value[0]) && is_array($value[0])) {
                foreach ($value as $subSchema) {
                    if (!is_array($subSchema)) {
                        continue;
                    }

                    /** @var array<string, mixed> $subSchema */
                    if (!$this->isSchemaObject($subSchema)) {
                        continue;
                    }

                    $this->registerSchemas($subSchema, $baseUri);
                }

                continue;
            }

            /** @var array<string, mixed> $value */
            // Check if it's a schema object (has schema keywords)
            if ($this->isSchemaObject($value)) {
                $this->registerSchemas($value, $baseUri);

                continue;
            }

            // Check if it's an associative array that might contain schemas
            // (e.g., definitions, properties, patternProperties, $defs)
            foreach ($value as $nestedValue) {
                if (!is_array($nestedValue)) {
                    continue;
                }

                /** @var array<string, mixed> $nestedValue */
                if (!$this->isSchemaObject($nestedValue)) {
                    continue;
                }

                $this->registerSchemas($nestedValue, $baseUri);
            }
        }
    }

    /**
     * Create a validator instance for a schema based on its $schema property.
     *
     * Detects the draft version from the $schema URI and instantiates the appropriate
     * validator. If no $schema is present or the draft cannot be detected, returns null
     * to indicate the current validator should be used.
     *
     * @param array<string, mixed>|bool $schema The schema to create a validator for
     *
     * @return null|ValidatorInterface The appropriate validator or null to use current
     */
    protected function createValidatorForSchema(array|bool $schema): ?ValidatorInterface
    {
        // Boolean schemas don't have draft versions
        if (!is_array($schema)) {
            return null;
        }

        // No $schema property means use current validator
        if (!isset($schema['$schema']) || !is_string($schema['$schema'])) {
            return null;
        }

        $schemaUri = $schema['$schema'];

        // Detect draft version from $schema URI
        $draftClass = match (true) {
            str_contains($schemaUri, 'draft-04') || str_contains($schemaUri, 'draft/4') => Draft04Validator::class,
            str_contains($schemaUri, 'draft-06') || str_contains($schemaUri, 'draft/6') => Draft06Validator::class,
            str_contains($schemaUri, 'draft-07') || str_contains($schemaUri, 'draft/7') => Draft07Validator::class,
            str_contains($schemaUri, '2019-09') || str_contains($schemaUri, 'draft/2019-09') => Draft201909Validator::class,
            str_contains($schemaUri, '2020-12') || str_contains($schemaUri, 'draft/2020-12') => Draft202012Validator::class,
            default => null,
        };

        // If we couldn't detect the draft, use current validator
        if ($draftClass === null) {
            return null;
        }

        // If the detected draft matches the current validator, no need to create a new one
        if ($draftClass === static::class) {
            return null;
        }

        // Create a new validator for the detected draft
        return new $draftClass($this->schemaLoader, $this->enableFormatValidation);
    }

    /**
     * Normalize a URI path by resolving . and .. segments.
     *
     * @param string $path The path to normalize
     *
     * @return string The normalized path
     */
    private function normalizePath(string $path): string
    {
        // Extract fragment if present
        $fragment = '';

        if (str_contains($path, '#')) {
            $fragmentPos = mb_strpos($path, '#');
            assert($fragmentPos !== false);
            $fragment = mb_substr($path, $fragmentPos);
            $path = mb_substr($path, 0, $fragmentPos);
        }

        // Check if path ends with / (important for base URIs)
        $trailingSlash = str_ends_with($path, '/');

        // Split path into segments
        $segments = explode('/', $path);
        $output = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                // Skip empty segments and current directory references
                continue;
            }

            if ($segment === '.') {
                // Skip empty segments and current directory references
                continue;
            }

            if ($segment === '..') {
                // Go up one level (remove last segment from output)
                array_pop($output);
            } else {
                // Add segment to output
                $output[] = $segment;
            }
        }

        // Rebuild path, preserving leading slash
        $result = '/'.implode('/', $output);

        // Preserve trailing slash if original had one
        if ($trailingSlash && !str_ends_with($result, '/')) {
            $result .= '/';
        }

        return $result.$fragment;
    }

    /**
     * Check if an array looks like a schema object.
     *
     * @param array<string, mixed> $value The value to check
     *
     * @return bool True if it looks like a schema
     */
    private function isSchemaObject(array $value): bool
    {
        // Schema keywords that indicate this is a schema object
        $schemaKeywords = ['type', 'properties', 'items', 'required', 'minimum', 'maximum',
            'minLength', 'maxLength', 'pattern', 'format', 'enum', 'const',
            'allOf', 'anyOf', 'oneOf', 'not', 'if', 'then', 'else',
            '$ref', '$id', 'id', 'definitions', '$defs', '$anchor', '$dynamicAnchor',
            '$recursiveAnchor', '$recursiveRef', '$dynamicRef', '$comment'];

        return array_any($schemaKeywords, fn ($keyword): bool => isset($value[$keyword]));
    }
}
