<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Facades;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\JsonSchemaManager;
use Cline\JsonSchema\ValueObjects\ValidationResult;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * Laravel facade for JSON Schema validation operations.
 *
 * Provides static access to the JsonSchemaManager for validating data against
 * JSON schemas. The facade simplifies validation calls and integrates seamlessly
 * with Laravel's service container and dependency injection system.
 *
 * ```php
 * use Cline\JsonSchema\Facades\JsonSchema;
 *
 * $result = JsonSchema::validate($data, [
 *     'type' => 'object',
 *     'properties' => [
 *         'name' => ['type' => 'string'],
 *         'age' => ['type' => 'integer', 'minimum' => 0],
 *     ],
 *     'required' => ['name'],
 * ]);
 *
 * if ($result->isValid()) {
 *     // Data is valid
 * }
 * ```
 *
 * @method static ValidationResult validate(mixed $data, array<string, mixed> $schema, ?Draft $draft = null) Validate data against a JSON schema definition
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see JsonSchemaManager
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Core Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Validation Specification
 * @see https://json-schema.org/understanding-json-schema/ Understanding JSON Schema Guide
 * @see https://laravel.com/docs/facades Laravel Facades Documentation
 */
final class JsonSchema extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * @return string The service container binding key for JsonSchemaManager
     */
    #[Override()]
    protected static function getFacadeAccessor(): string
    {
        return JsonSchemaManager::class;
    }
}
