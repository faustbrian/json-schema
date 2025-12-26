<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Contracts;

use Cline\JsonSchema\Exceptions\UnresolvedReferenceException;

/**
 * Contract for resolving JSON Schema references.
 *
 * Defines the interface for resolving $ref pointers within JSON Schema
 * documents. Handles both internal references (within the same document)
 * and external references (to other schema documents).
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/structuring#dollarref
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8.2.3
 * @see https://datatracker.ietf.org/doc/html/rfc6901 JSON Pointer (RFC 6901)
 */
interface ReferenceResolverInterface
{
    /**
     * Resolve a reference pointer.
     *
     * Takes a $ref value (JSON Pointer or URI) and resolves it to the
     * referenced schema fragment. Supports both internal references
     * (e.g., '#/definitions/User') and external references
     * (e.g., 'http://example.com/schema.json#/definitions/User').
     *
     * @param string          $reference The reference to resolve (JSON Pointer or URI)
     * @param SchemaInterface $schema    The schema context for resolving the reference
     *
     * @throws UnresolvedReferenceException If the reference cannot be resolved
     * @return array<string, mixed>         The resolved schema fragment
     */
    public function resolve(string $reference, SchemaInterface $schema): array;

    /**
     * Check if a reference can be resolved.
     *
     * Determines whether the given reference can be successfully resolved
     * without throwing an exception.
     *
     * @param string          $reference The reference to check
     * @param SchemaInterface $schema    The schema context
     *
     * @return bool True if the reference can be resolved, false otherwise
     */
    public function canResolve(string $reference, SchemaInterface $schema): bool;
}
