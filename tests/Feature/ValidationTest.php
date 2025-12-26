<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Feature;

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Facades\JsonSchema;
use Tests\TestCase;

use function expect;

/**
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class ValidationTest extends TestCase
{
    public function test_validates_simple_string_schema(): void
    {
        $schema = ['type' => 'string'];

        $result = JsonSchema::validate('hello', $schema);

        expect($result->isValid())->toBeTrue();
    }

    public function test_rejects_invalid_type(): void
    {
        $schema = ['type' => 'string'];

        $result = JsonSchema::validate(123, $schema);

        expect($result->isInvalid())->toBeTrue();
    }

    public function test_validates_object_with_properties(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
            'required' => ['name'],
        ];

        $data = ['name' => 'John', 'age' => 30];

        $result = JsonSchema::validate($data, $schema);

        expect($result->isValid())->toBeTrue();
    }

    public function test_validates_with_specific_draft(): void
    {
        $schema = ['type' => 'number', 'minimum' => 0];

        $result = JsonSchema::validate(5, $schema, Draft::Draft07);

        expect($result->isValid())->toBeTrue();
    }
}
