<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Enums;

/**
 * JSON Schema format validation types.
 *
 * Represents the semantic validation formats defined in JSON Schema.
 * Format validation is applied via the "format" keyword and provides
 * additional constraints beyond basic type checking.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/string#format
 * @see https://json-schema.org/draft/2020-12/json-schema-validation#section-7
 */
enum Format: string
{
    /**
     * RFC 3339 date-time format.
     *
     * Full date and time with timezone (e.g., "2024-01-15T14:30:00Z").
     *
     * @see https://tools.ietf.org/html/rfc3339#section-5.6
     */
    case DateTime = 'date-time';

    /**
     * RFC 3339 date format.
     *
     * Full date without time (e.g., "2024-01-15").
     *
     * @see https://tools.ietf.org/html/rfc3339#section-5.6
     */
    case Date = 'date';

    /**
     * RFC 3339 time format.
     *
     * Time without date (e.g., "14:30:00" or "14:30:00Z").
     *
     * @see https://tools.ietf.org/html/rfc3339#section-5.6
     */
    case Time = 'time';

    /**
     * RFC 3339 duration format.
     *
     * ISO 8601 duration (e.g., "P3Y6M4DT12H30M5S").
     *
     * @see https://tools.ietf.org/html/rfc3339#appendix-A
     */
    case Duration = 'duration';

    /**
     * RFC 5321 email address format.
     *
     * Standard email address validation (e.g., "user@example.com").
     *
     * @see https://tools.ietf.org/html/rfc5321#section-4.1.2
     */
    case Email = 'email';

    /**
     * RFC 6531 internationalized email address format.
     *
     * Email addresses with international characters (e.g., "用户@例え.jp").
     *
     * @see https://tools.ietf.org/html/rfc6531
     */
    case IdnEmail = 'idn-email';

    /**
     * RFC 1123 hostname format.
     *
     * Internet hostname (e.g., "example.com", "api.example.com").
     *
     * @see https://tools.ietf.org/html/rfc1123#section-2.1
     */
    case Hostname = 'hostname';

    /**
     * RFC 5890 internationalized hostname format.
     *
     * Hostname with international characters (e.g., "例え.jp").
     *
     * @see https://tools.ietf.org/html/rfc5890#section-2.3.2.3
     */
    case IdnHostname = 'idn-hostname';

    /**
     * IPv4 address format.
     *
     * Dotted-quad IPv4 address (e.g., "192.168.1.1").
     *
     * @see https://tools.ietf.org/html/rfc2673#section-3.2
     */
    case Ipv4 = 'ipv4';

    /**
     * IPv6 address format.
     *
     * Full or compressed IPv6 address (e.g., "2001:0db8::1").
     *
     * @see https://tools.ietf.org/html/rfc4291#section-2.2
     */
    case Ipv6 = 'ipv6';

    /**
     * RFC 3986 URI format.
     *
     * Absolute or relative URI (e.g., "https://example.com/path?query=value").
     *
     * @see https://tools.ietf.org/html/rfc3986
     */
    case Uri = 'uri';

    /**
     * RFC 3986 URI reference format.
     *
     * URI or relative reference (e.g., "/path", "../file", "#fragment").
     *
     * @see https://tools.ietf.org/html/rfc3986#section-4.1
     */
    case UriReference = 'uri-reference';

    /**
     * RFC 3987 Internationalized Resource Identifier format.
     *
     * URI with international characters (e.g., "https://例え.jp/パス").
     *
     * @see https://tools.ietf.org/html/rfc3987
     */
    case Iri = 'iri';

    /**
     * RFC 3987 IRI reference format.
     *
     * IRI or relative reference with international characters.
     *
     * @see https://tools.ietf.org/html/rfc3987#section-2.1
     */
    case IriReference = 'iri-reference';

    /**
     * RFC 6570 URI template format.
     *
     * URI template with variable placeholders (e.g., "/users/{id}").
     *
     * @see https://tools.ietf.org/html/rfc6570
     */
    case UriTemplate = 'uri-template';

    /**
     * RFC 6901 JSON Pointer format.
     *
     * JSON Pointer for referencing document locations (e.g., "/foo/0/bar").
     *
     * @see https://tools.ietf.org/html/rfc6901
     */
    case JsonPointer = 'json-pointer';

    /**
     * Relative JSON Pointer format.
     *
     * Relative path from current location (e.g., "0/name", "1/0").
     *
     * @see https://tools.ietf.org/html/draft-handrews-relative-json-pointer-01
     */
    case RelativeJsonPointer = 'relative-json-pointer';

    /**
     * ECMA-262 regular expression format.
     *
     * Valid JavaScript regular expression pattern.
     *
     * @see https://www.ecma-international.org/ecma-262/
     */
    case Regex = 'regex';

    /**
     * RFC 4122 UUID format.
     *
     * Universally Unique Identifier (e.g., "550e8400-e29b-41d4-a716-446655440000").
     *
     * @see https://tools.ietf.org/html/rfc4122
     */
    case Uuid = 'uuid';
}
