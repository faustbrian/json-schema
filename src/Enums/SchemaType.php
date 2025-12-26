<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Enums;

/**
 * JSON Schema primitive types.
 *
 * Represents the seven primitive types defined in JSON Schema.
 * These types correspond to JSON data types and are used in the
 * "type" keyword of JSON Schema documents.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/type
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-4.2.1
 */
enum SchemaType: string
{
    /**
     * Null type - represents JSON null.
     */
    case Null = 'null';

    /**
     * Boolean type - represents true or false.
     */
    case Boolean = 'boolean';

    /**
     * Object type - represents JSON objects (key-value pairs).
     */
    case Object = 'object';

    /**
     * Array type - represents JSON arrays (ordered lists).
     */
    case Array = 'array';

    /**
     * Number type - represents any JSON number (integer or floating-point).
     */
    case Number = 'number';

    /**
     * String type - represents JSON strings.
     */
    case String = 'string';

    /**
     * Integer type - represents whole numbers (subset of number).
     */
    case Integer = 'integer';
}
