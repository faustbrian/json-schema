<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Contracts\ReferenceResolverInterface;
use Cline\JsonSchema\Contracts\SchemaInterface;
use Cline\JsonSchema\Exceptions\CannotResolveReferenceException;
use Cline\JsonSchema\Exceptions\InvalidJsonPointerException;
use Cline\JsonSchema\Exceptions\UnresolvedReferenceException;

use function array_key_exists;
use function explode;
use function is_array;
use function mb_substr;
use function str_replace;
use function str_starts_with;

/**
 * Resolves JSON Schema $ref references using JSON Pointer syntax.
 *
 * Implements JSON Pointer (RFC 6901) resolution for JSON Schema $ref keywords.
 * Currently supports internal references within the same document (starting with #/)
 * but does not handle external references to other schema files.
 *
 * References allow schema reuse by pointing to definitions elsewhere in the schema
 * document, enabling DRY principles and modular schema design.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3 $ref keyword specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-9.4 Direct references with $ref
 * @see https://datatracker.ietf.org/doc/html/rfc6901 JSON Pointer (RFC 6901)
 * @see https://json-schema.org/understanding-json-schema/structuring Structuring schemas with $ref
 *
 * @psalm-immutable
 */
final readonly class ReferenceResolver implements ReferenceResolverInterface
{
    /**
     * Resolve a $ref JSON Pointer to its target schema fragment.
     *
     * Takes a $ref value (JSON Pointer like "#/definitions/address") and traverses
     * the schema document to locate and return the referenced schema fragment.
     * Only internal references within the same document (starting with #/) are
     * currently supported.
     *
     * The method implements RFC 6901 JSON Pointer syntax, including proper handling
     * of escape sequences (~0 for ~ and ~1 for /).
     *
     * ```php
     * $resolver = new ReferenceResolver();
     * $schema = Schema::fromArray([
     *     'definitions' => [
     *         'address' => ['type' => 'object', 'properties' => [...]]
     *     ]
     * ]);
     *
     * // Resolves to the address schema definition
     * $addressSchema = $resolver->resolve('#/definitions/address', $schema);
     * ```
     *
     * @param string          $reference JSON Pointer reference (e.g., "#/definitions/address")
     * @param SchemaInterface $schema    The root schema containing the reference target
     *
     * @throws CannotResolveReferenceException If the reference format is unsupported (e.g., external refs)
     * @throws InvalidJsonPointerException     If the pointer path doesn't exist in the schema
     *
     * @return array<string, mixed> The resolved schema fragment
     */
    public function resolve(string $reference, SchemaInterface $schema): array
    {
        // Only handle internal references (starting with #/)
        if (!str_starts_with($reference, '#/')) {
            throw CannotResolveReferenceException::forReference($reference);
        }

        // Remove the #/ prefix
        $pointer = mb_substr($reference, 2);

        // Split the pointer into segments
        $segments = explode('/', $pointer);

        // Traverse the schema following the pointer
        $current = $schema->toArray();

        foreach ($segments as $segment) {
            // Decode JSON Pointer special characters
            $segment = str_replace('~1', '/', $segment);
            $segment = str_replace('~0', '~', $segment);

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                throw InvalidJsonPointerException::forPointer($reference);
            }

            $current = $current[$segment];
        }

        if (!is_array($current)) {
            throw InvalidJsonPointerException::forPointer($reference);
        }

        return $current;
    }

    /**
     * Check if a reference can be successfully resolved.
     *
     * Tests whether the given reference can be resolved without throwing an
     * exception. Useful for validation logic that needs to check reference
     * validity before attempting resolution.
     *
     * @param string          $reference JSON Pointer reference to test
     * @param SchemaInterface $schema    The schema context for resolution
     *
     * @return bool True if the reference resolves successfully, false otherwise
     */
    public function canResolve(string $reference, SchemaInterface $schema): bool
    {
        try {
            $this->resolve($reference, $schema);

            return true;
        } catch (UnresolvedReferenceException) {
            return false;
        }
    }
}
