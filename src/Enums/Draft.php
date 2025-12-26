<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Enums;

use function str_contains;

/**
 * JSON Schema draft versions.
 *
 * Represents the supported JSON Schema specification versions. Each version
 * introduces new keywords and validation rules. The draft version is typically
 * specified in the $schema keyword of a JSON Schema document.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/specification
 * @see https://json-schema.org/understanding-json-schema/reference/schema#schema
 */
enum Draft: string
{
    /**
     * JSON Schema Draft 04.
     *
     * @see https://tools.ietf.org/html/draft-fge-json-schema-validation-00
     */
    case Draft04 = 'http://json-schema.org/draft-04/schema#';

    /**
     * JSON Schema Draft 06.
     *
     * Introduced: const, contains, propertyNames, examples
     *
     * @see https://tools.ietf.org/html/draft-wright-json-schema-validation-01
     */
    case Draft06 = 'http://json-schema.org/draft-06/schema#';

    /**
     * JSON Schema Draft 07.
     *
     * Introduced: if/then/else, readOnly, writeOnly, contentEncoding, contentMediaType
     *
     * @see https://tools.ietf.org/html/draft-handrews-json-schema-validation-01
     */
    case Draft07 = 'http://json-schema.org/draft-07/schema#';

    /**
     * JSON Schema 2019-09.
     *
     * Introduced: $defs, unevaluatedProperties, unevaluatedItems, maxContains, minContains
     * dependentSchemas, dependentRequired
     *
     * @see https://json-schema.org/specification-links.html#2019-09-formerly-known-as-draft-8
     */
    case Draft201909 = 'https://json-schema.org/draft/2019-09/schema';

    /**
     * JSON Schema 2020-12 (latest).
     *
     * Introduced: prefixItems, $dynamicRef, $dynamicAnchor
     *
     * @see https://json-schema.org/specification-links.html#2020-12-formerly-known-as-draft-2020-12
     */
    case Draft202012 = 'https://json-schema.org/draft/2020-12/schema';

    /**
     * Get the draft version from a schema URI.
     *
     * Attempts to detect the draft version from a $schema keyword value.
     * Returns null if the URI doesn't match any known draft version.
     *
     * @param string $schemaUri The $schema URI to parse
     *
     * @return null|self The matched draft version or null
     */
    public static function fromSchemaUri(string $schemaUri): ?self
    {
        return match (true) {
            str_contains($schemaUri, 'draft-04') => self::Draft04,
            str_contains($schemaUri, 'draft-06') => self::Draft06,
            str_contains($schemaUri, 'draft-07') => self::Draft07,
            str_contains($schemaUri, '2019-09') => self::Draft201909,
            str_contains($schemaUri, '2020-12') => self::Draft202012,
            default => null,
        };
    }

    /**
     * Get a human-readable label for the draft version.
     *
     * @return string The human-readable draft label
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft04 => 'Draft 04',
            self::Draft06 => 'Draft 06',
            self::Draft07 => 'Draft 07',
            self::Draft201909 => 'Draft 2019-09',
            self::Draft202012 => 'Draft 2020-12',
        };
    }
}
