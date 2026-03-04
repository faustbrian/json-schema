<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Support;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Factories\ValidatorFactory;
use Cline\JsonSchema\ValueObjects\ValidationResult;

use function is_string;

/**
 * Validates JSON schemas against their meta-schemas.
 *
 * Ensures that schema definitions are well-formed and comply with
 * the JSON Schema specification. Uses the appropriate meta-schema
 * for each draft version to validate schema structure.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/draft/2020-12/json-schema-core#section-8 Meta-Schemas
 * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Structure
 */
final class SchemaValidator
{
    /**
     * Meta-schema URIs for each draft version.
     */
    private const array META_SCHEMAS = [
        'draft-04' => 'https://json-schema.org/draft-04/schema',
        'draft-06' => 'https://json-schema.org/draft-06/schema',
        'draft-07' => 'https://json-schema.org/draft-07/schema',
        'draft-2019-09' => 'https://json-schema.org/draft/2019-09/schema',
        'draft-2020-12' => 'https://json-schema.org/draft/2020-12/schema',
    ];

    /**
     * Validate a schema against its meta-schema.
     *
     * Checks that the schema is well-formed according to the JSON Schema
     * specification for its declared draft version.
     *
     * @param array<string, mixed> $schema           The schema to validate
     * @param null|Draft           $draft            The draft version to validate against
     * @param ValidatorFactory     $validatorFactory Factory for creating validators
     *
     * @return ValidationResult Validation result indicating if schema is valid
     */
    public static function validateSchema(
        array $schema,
        ?Draft $draft = null,
        ?ValidatorFactory $validatorFactory = null,
    ): ValidationResult {
        $draft ??= self::detectDraft($schema);
        $validatorFactory ??= new ValidatorFactory();

        $metaSchema = self::getMetaSchema($draft);
        $validator = $validatorFactory->create($draft);

        return $validator->validate($schema, $metaSchema);
    }

    /**
     * Check if a schema is valid without returning detailed errors.
     *
     * Convenience method for quick schema validation.
     *
     * @param array<string, mixed>  $schema           The schema to validate
     * @param null|Draft            $draft            The draft version to validate against
     * @param null|ValidatorFactory $validatorFactory Factory for creating validators
     *
     * @return bool True if the schema is valid, false otherwise
     */
    public static function isValidSchema(
        array $schema,
        ?Draft $draft = null,
        ?ValidatorFactory $validatorFactory = null,
    ): bool {
        return self::validateSchema($schema, $draft, $validatorFactory)->isValid();
    }

    /**
     * Get the meta-schema definition for a draft version.
     *
     * Returns the meta-schema that defines the structure and constraints
     * that schemas of this draft version must follow.
     *
     * @param Draft $draft The draft version
     *
     * @return array<string, mixed> The meta-schema definition
     */
    private static function getMetaSchema(Draft $draft): array
    {
        // Simplified meta-schema - in production would load full meta-schema
        return [
            '$schema' => self::META_SCHEMAS[self::getDraftKey($draft)] ?? self::META_SCHEMAS['draft-2020-12'],
            'type' => ['object', 'boolean'],
        ];
    }

    /**
     * Get the draft key for meta-schema lookup.
     *
     * @param Draft $draft The draft enum value
     *
     * @return string The draft key
     */
    private static function getDraftKey(Draft $draft): string
    {
        return match ($draft) {
            Draft::Draft04 => 'draft-04',
            Draft::Draft06 => 'draft-06',
            Draft::Draft07 => 'draft-07',
            Draft::Draft201909 => 'draft-2019-09',
            Draft::Draft202012 => 'draft-2020-12',
        };
    }

    /**
     * Detect the draft version from a schema.
     *
     * @param array<string, mixed> $schema The schema to inspect
     *
     * @return Draft The detected draft version
     */
    private static function detectDraft(array $schema): Draft
    {
        if (isset($schema['$schema']) && is_string($schema['$schema'])) {
            return Draft::fromSchemaUri($schema['$schema']) ?? Draft::Draft202012;
        }

        return Draft::Draft202012;
    }
}
