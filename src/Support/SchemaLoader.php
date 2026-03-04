<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use function explode;
use function file_exists;
use function file_get_contents;
use function is_array;
use function mb_substr;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;

/**
 * Schema loader for resolving external schema references.
 *
 * Loads external schema documents referenced via URIs in $ref and $schema keywords.
 * Supports loading from the JSON Schema Test Suite remotes directory, json-schema.org
 * metaschemas, and vocabulary schemas. Includes caching to avoid redundant file reads.
 *
 * This loader is primarily used for test suite compliance and metaschema resolution,
 * enabling validation against official JSON Schema specifications.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3 $ref keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8 Schema identification and addressing
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-9 Schema references
 * @see https://json-schema.org/understanding-json-schema/structuring Structuring complex schemas
 * @see https://datatracker.ietf.org/doc/html/rfc3986 URI generic syntax (RFC 3986)
 */
final class SchemaLoader
{
    /**
     * Cache of loaded schemas indexed by URL.
     *
     * Prevents redundant file I/O by storing previously loaded schemas in memory
     * for the lifetime of the loader instance.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    /**
     * Path to the JSON Schema Test Suite remotes directory.
     */
    private readonly string $remotesPath;

    /**
     * Create a new schema loader instance.
     *
     * @param null|string $remotesPath Optional path to the remotes directory. Defaults to
     *                                 the bundled JSON Schema Test Suite remotes location.
     */
    public function __construct(?string $remotesPath = null)
    {
        $this->remotesPath = $remotesPath ?? __DIR__.'/../../compliance/JSON-Schema-Test-Suite/remotes';
    }

    /**
     * Load a schema from a URL or URI.
     *
     * Attempts to load a schema from various sources based on the URL pattern:
     * - localhost:1234 URLs: Loaded from Test Suite remotes directory
     * - json-schema.org metaschemas: Loaded from bundled metaschema files
     * - json-schema.org vocabularies: Loaded from Test Suite remotes
     *
     * Results are cached to improve performance on repeated loads.
     *
     * @param string $url The schema URL or URI to load
     *
     * @return null|array<string, mixed> The loaded schema, or null if not found
     */
    public function load(string $url): ?array
    {
        // Check cache
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        // Handle localhost:1234 URLs (test suite remotes) - both http and https
        if (str_starts_with($url, 'http://localhost:1234/') || str_starts_with($url, 'https://localhost:1234/')) {
            return $this->loadFromRemotes($url);
        }

        // Handle json-schema.org meta/* vocabularies - both http and https
        if ((str_starts_with($url, 'http://json-schema.org/') || str_starts_with($url, 'https://json-schema.org/'))
            && str_contains($url, '/meta/')) {
            return $this->loadMetaVocabulary($url);
        }

        // Handle json-schema.org metaschemas - both http and https
        if (str_starts_with($url, 'http://json-schema.org/') || str_starts_with($url, 'https://json-schema.org/')) {
            return $this->loadMetaschema($url);
        }

        return null;
    }

    /**
     * Load schema from the Test Suite remotes directory.
     *
     * Handles localhost:1234 URLs used in the JSON Schema Test Suite by mapping
     * them to local files in the remotes directory. Supports JSON Pointer fragments
     * for loading specific schema sections.
     *
     * @param string $url The localhost:1234 URL to load
     *
     * @return null|array<string, mixed> The schema, or null if file not found
     */
    private function loadFromRemotes(string $url): ?array
    {
        // Remove http://localhost:1234/ or https://localhost:1234/ prefix
        $path = str_replace(['https://localhost:1234/', 'http://localhost:1234/'], '', $url);

        // Remove fragment if present
        $fragment = null;

        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
        }

        $filePath = $this->remotesPath.'/'.$path;

        if (!file_exists($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        $schema = JsonDecoder::decode($contents);

        if (!is_array($schema)) {
            return null;
        }

        /** @var array<string, mixed> $schema */

        // Cache the full schema
        $baseUrl = 'http://localhost:1234/'.$path;
        $this->cache[$baseUrl] = $schema;

        // Resolve fragment if present
        if ($fragment !== null) {
            return $this->resolveFragment($schema, $fragment);
        }

        return $schema;
    }

    /**
     * Load a JSON Schema metaschema from bundled resources.
     *
     * Maps json-schema.org metaschema URLs to locally bundled metaschema files
     * for drafts 04, 06, 07, 2019-09, and 2020-12. Metaschemas define the
     * schema of schemas themselves and are used for validating schema documents.
     *
     * @param string $url The metaschema URL (e.g., https://json-schema.org/draft/2020-12/schema)
     *
     * @return null|array<string, mixed> The metaschema, or null if not found
     */
    private function loadMetaschema(string $url): ?array
    {
        // Map metaschema URLs to local files
        $metaschemaPath = match (true) {
            str_contains($url, 'draft-04') || str_contains($url, 'draft04') => __DIR__.'/../../resources/metaschemas/draft-04.json',
            str_contains($url, 'draft-06') || str_contains($url, 'draft06') => __DIR__.'/../../resources/metaschemas/draft-06.json',
            str_contains($url, 'draft-07') || str_contains($url, 'draft07') => __DIR__.'/../../resources/metaschemas/draft-07.json',
            str_contains($url, '2019-09') => __DIR__.'/../../resources/metaschemas/draft-2019-09.json',
            str_contains($url, '2020-12') => __DIR__.'/../../resources/metaschemas/draft-2020-12.json',
            default => null,
        };

        if ($metaschemaPath === null || !file_exists($metaschemaPath)) {
            return null;
        }

        $contents = file_get_contents($metaschemaPath);

        if ($contents === false) {
            return null;
        }

        $metaschema = JsonDecoder::decode($contents);

        if (!is_array($metaschema)) {
            return null;
        }

        /** @var array<string, mixed> $metaschema */
        $this->cache[$url] = $metaschema;

        return $metaschema;
    }

    /**
     * Load a JSON Schema vocabulary definition.
     *
     * Loads vocabulary schemas from the Test Suite remotes directory. Vocabularies
     * define sets of keywords for different purposes (core, validation, applicator, etc.)
     * and are used in Draft 2019-09 and later.
     *
     * @param string $url The vocabulary URL (e.g., https://json-schema.org/draft/2019-09/meta/core)
     *
     * @return null|array<string, mixed> The vocabulary schema, or null if not found
     */
    private function loadMetaVocabulary(string $url): ?array
    {
        // Extract path like "draft/2019-09/meta/core" from URL
        $path = str_replace(['https://json-schema.org/', 'http://json-schema.org/'], '', $url);

        // Map to remotes directory structure: draft/2019-09/meta/core -> draft201909/meta/core
        // Remove the slash after "draft" and remove hyphens from the draft version only
        $path = preg_replace('#^draft/(\d{4})-(\d{2})/#', 'draft$1$2/', $path);

        $filePath = $this->remotesPath.'/'.$path;

        if (!file_exists($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        $schema = JsonDecoder::decode($contents);

        if (!is_array($schema)) {
            return null;
        }

        /** @var array<string, mixed> $schema */
        $this->cache[$url] = $schema;

        return $schema;
    }

    /**
     * Resolve a JSON Pointer fragment within a schema.
     *
     * Traverses a schema document using JSON Pointer syntax to locate a specific
     * fragment. Handles RFC 6901 escape sequences (~0 for ~, ~1 for /).
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6901 JSON Pointer (RFC 6901)
     *
     * @param array<string, mixed> $schema   The schema document to traverse
     * @param string               $fragment The JSON Pointer fragment (e.g., "/definitions/address")
     *
     * @return null|array<string, mixed> The resolved fragment, or null if not found
     */
    private function resolveFragment(array $schema, string $fragment): ?array
    {
        // Handle root reference
        if ($fragment === '' || $fragment === '/') {
            return $schema;
        }

        // Remove leading slash if present
        if (str_starts_with($fragment, '/')) {
            $fragment = mb_substr($fragment, 1);
        }

        $parts = explode('/', $fragment);
        $current = $schema;

        foreach ($parts as $part) {
            // Decode JSON Pointer escapes
            $part = str_replace('~1', '/', str_replace('~0', '~', $part));

            if (!is_array($current) || !isset($current[$part])) {
                return null;
            }

            $current = $current[$part];
        }

        if (!is_array($current)) {
            return null;
        }

        /** @var array<string, mixed> $current */

        return $current;
    }
}
