<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Support;

use Cline\JsonSchema\Exceptions\CannotResolveReferenceException;
use Cline\JsonSchema\Exceptions\InvalidJsonPointerException;
use Cline\JsonSchema\Support\ReferenceResolver;
use Cline\JsonSchema\ValueObjects\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
#[CoversClass(ReferenceResolver::class)]
#[Small()]
final class ReferenceResolverTest extends TestCase
{
    private ReferenceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange
        $this->resolver = new ReferenceResolver();
    }

    #[Test()]
    #[TestDox('Resolves simple internal reference to definitions')]
    #[Group('happy-path')]
    public function resolves_simple_internal_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'address' => [
                    'type' => 'object',
                    'properties' => [
                        'street' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                    ],
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/address', $schema);

        // Assert
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'street' => ['type' => 'string'],
                'city' => ['type' => 'string'],
            ],
        ], $result);
    }

    #[Test()]
    #[TestDox('Resolves nested path reference through multiple levels')]
    #[Group('happy-path')]
    public function resolves_nested_path_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'address' => [
                            'type' => 'object',
                            'properties' => [
                                'street' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/user/properties/address', $schema);

        // Assert
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'street' => ['type' => 'string'],
            ],
        ], $result);
    }

    #[Test()]
    #[TestDox('Resolves deep nested reference with many levels')]
    #[Group('happy-path')]
    public function resolves_deep_nested_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => [
                            'e' => [
                                'type' => 'string',
                                'minLength' => 5,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/a/b/c/d/e', $schema);

        // Assert
        $this->assertSame([
            'type' => 'string',
            'minLength' => 5,
        ], $result);
    }

    #[Test()]
    #[TestDox('Resolves single segment reference from root')]
    #[Group('happy-path')]
    public function resolves_single_segment_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'number'],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/properties', $schema);

        // Assert
        $this->assertSame([
            'name' => ['type' => 'string'],
            'age' => ['type' => 'number'],
        ], $result);
    }

    #[Test()]
    #[TestDox('Resolves reference with array index notation')]
    #[Group('happy-path')]
    public function resolves_reference_with_array_index(): void
    {
        // Arrange
        $schema = new Schema([
            'items' => [
                ['type' => 'string'],
                ['type' => 'number'],
                ['type' => 'boolean'],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/items/1', $schema);

        // Assert
        $this->assertSame(['type' => 'number'], $result);
    }

    #[Test()]
    #[TestDox('Throws exception for external reference not starting with #/')]
    #[Group('sad-path')]
    public function throws_exception_for_external_reference(): void
    {
        // Arrange
        $schema = new Schema(['type' => 'object']);

        // Act & Assert
        $this->expectException(CannotResolveReferenceException::class);
        $this->expectExceptionMessage('Unable to resolve reference: http://example.com/schema.json');

        $this->resolver->resolve('http://example.com/schema.json', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception for relative external reference')]
    #[Group('sad-path')]
    public function throws_exception_for_relative_external_reference(): void
    {
        // Arrange
        $schema = new Schema(['type' => 'object']);

        // Act & Assert
        $this->expectException(CannotResolveReferenceException::class);
        $this->expectExceptionMessage('Unable to resolve reference: ./other-schema.json');

        $this->resolver->resolve('./other-schema.json', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception for reference starting with # but not #/')]
    #[Group('sad-path')]
    public function throws_exception_for_anchor_reference(): void
    {
        // Arrange
        $schema = new Schema(['type' => 'object']);

        // Act & Assert
        $this->expectException(CannotResolveReferenceException::class);
        $this->expectExceptionMessage('Unable to resolve reference: #anchor');

        $this->resolver->resolve('#anchor', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception for non-existent path in schema')]
    #[Group('sad-path')]
    public function throws_exception_for_non_existent_path(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'address' => ['type' => 'object'],
            ],
        ]);

        // Act & Assert
        $this->expectException(InvalidJsonPointerException::class);
        $this->expectExceptionMessage('Invalid JSON pointer: #/definitions/user');

        $this->resolver->resolve('#/definitions/user', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception for non-existent segment in path')]
    #[Group('sad-path')]
    public function throws_exception_for_non_existent_segment(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act & Assert
        $this->expectException(InvalidJsonPointerException::class);
        $this->expectExceptionMessage('Invalid JSON pointer: #/definitions/user/properties');

        $this->resolver->resolve('#/definitions/user/properties', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception when resolved value is not an array')]
    #[Group('sad-path')]
    public function throws_exception_for_non_array_final_value(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act & Assert
        $this->expectException(InvalidJsonPointerException::class);
        $this->expectExceptionMessage('Invalid JSON pointer: #/definitions/user/type');

        $this->resolver->resolve('#/definitions/user/type', $schema);
    }

    #[Test()]
    #[TestDox('Throws exception for reference to empty schema')]
    #[Group('sad-path')]
    public function throws_exception_for_reference_to_empty_schema(): void
    {
        // Arrange
        $schema = new Schema([]);

        // Act & Assert
        $this->expectException(InvalidJsonPointerException::class);
        $this->expectExceptionMessage('Invalid JSON pointer: #/definitions');

        $this->resolver->resolve('#/definitions', $schema);
    }

    #[Test()]
    #[TestDox('Resolves path with escaped tilde (~0)')]
    #[Group('edge-case')]
    public function resolves_path_with_escaped_tilde(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'my~field' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/my~0field', $schema);

        // Assert
        $this->assertSame(['type' => 'string'], $result);
    }

    #[Test()]
    #[TestDox('Resolves path with escaped slash (~1)')]
    #[Group('edge-case')]
    public function resolves_path_with_escaped_slash(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'my/field' => [
                    'type' => 'number',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/my~1field', $schema);

        // Assert
        $this->assertSame(['type' => 'number'], $result);
    }

    #[Test()]
    #[TestDox('Resolves path with combined escape sequences')]
    #[Group('edge-case')]
    public function resolves_path_with_combined_escapes(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'my~/field' => [
                    'type' => 'boolean',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/my~0~1field', $schema);

        // Assert
        $this->assertSame(['type' => 'boolean'], $result);
    }

    #[Test()]
    #[TestDox('Resolves path with special characters in keys')]
    #[Group('edge-case')]
    public function resolves_path_with_special_characters(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user-address' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/user-address', $schema);

        // Assert
        $this->assertSame(['type' => 'object'], $result);
    }

    #[Test()]
    #[TestDox('Resolves path with unicode characters in keys')]
    #[Group('edge-case')]
    public function resolves_path_with_unicode_characters(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'użytkownik' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/użytkownik', $schema);

        // Assert
        $this->assertSame(['type' => 'object'], $result);
    }

    #[Test()]
    #[TestDox('Resolves path with spaces in keys')]
    #[Group('edge-case')]
    public function resolves_path_with_spaces(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user address' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/user address', $schema);

        // Assert
        $this->assertSame(['type' => 'object'], $result);
    }

    #[Test()]
    #[TestDox('Resolves numeric string keys correctly')]
    #[Group('edge-case')]
    public function resolves_numeric_string_keys(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                '123' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->resolve('#/definitions/123', $schema);

        // Assert
        $this->assertSame(['type' => 'string'], $result);
    }

    #[Test()]
    #[TestDox('Returns true for valid resolvable reference')]
    #[Group('happy-path')]
    public function can_resolve_returns_true_for_valid_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'address' => ['type' => 'object'],
            ],
        ]);

        // Act
        $result = $this->resolver->canResolve('#/definitions/address', $schema);

        // Assert
        $this->assertTrue($result);
    }

    #[Test()]
    #[TestDox('Returns false for invalid non-resolvable reference')]
    #[Group('sad-path')]
    public function can_resolve_returns_false_for_invalid_reference(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'address' => ['type' => 'object'],
            ],
        ]);

        // Act
        $result = $this->resolver->canResolve('#/definitions/user', $schema);

        // Assert
        $this->assertFalse($result);
    }

    #[Test()]
    #[TestDox('Returns false for external reference')]
    #[Group('sad-path')]
    public function can_resolve_returns_false_for_external_reference(): void
    {
        // Arrange
        $schema = new Schema(['type' => 'object']);

        // Act
        $result = $this->resolver->canResolve('http://example.com/schema.json', $schema);

        // Assert
        $this->assertFalse($result);
    }

    #[Test()]
    #[TestDox('Returns false when resolved value is not an array')]
    #[Group('sad-path')]
    public function can_resolve_returns_false_for_non_array_value(): void
    {
        // Arrange
        $schema = new Schema([
            'definitions' => [
                'user' => [
                    'type' => 'object',
                ],
            ],
        ]);

        // Act
        $result = $this->resolver->canResolve('#/definitions/user/type', $schema);

        // Assert
        $this->assertFalse($result);
    }

    #[Test()]
    #[TestDox('Resolves complex real-world schema reference')]
    #[Group('happy-path')]
    public function resolves_complex_real_world_schema_reference(): void
    {
        // Arrange
        $schema = new Schema([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'definitions' => [
                'person' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'age' => ['type' => 'integer', 'minimum' => 0],
                        'address' => ['$ref' => '#/definitions/address'],
                    ],
                    'required' => ['name'],
                ],
                'address' => [
                    'type' => 'object',
                    'properties' => [
                        'street' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                        'zipCode' => ['type' => 'string', 'pattern' => '^\d{5}$'],
                    ],
                ],
            ],
            'type' => 'object',
            'properties' => [
                'users' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/definitions/person'],
                ],
            ],
        ]);

        // Act
        $personResult = $this->resolver->resolve('#/definitions/person', $schema);
        $addressResult = $this->resolver->resolve('#/definitions/address', $schema);

        // Assert
        $this->assertArrayHasKey('properties', $personResult);
        $this->assertArrayHasKey('required', $personResult);
        $this->assertEquals(['name'], $personResult['required']);

        $this->assertArrayHasKey('properties', $addressResult);
        $this->assertArrayHasKey('zipCode', $addressResult['properties']);
        $this->assertEquals('^\d{5}$', $addressResult['properties']['zipCode']['pattern']);
    }
}
