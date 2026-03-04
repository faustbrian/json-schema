<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Adapters;

use Cline\JsonSchema\Support\JsonDecoder;
use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\ValueObjects\ValidationResult;
use Cline\Prism\Contracts\PrismTestInterface;
use Cline\Prism\Contracts\ValidationResult as ComplianceValidationResult;

use function array_all;
use function str_contains;

/**
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class ComplianceAdapter implements PrismTestInterface
{
    /**
     * @param class-string  $validatorClass
     * @param array<string> $excludePaths
     */
    public function __construct(
        private string $name,
        private string $validatorClass,
        private string $testDirectory,
        private SchemaLoader $schemaLoader,
        private bool $enableFormatValidation = false,
        private array $excludePaths = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValidatorClass(): string
    {
        return $this->validatorClass;
    }

    public function getTestDirectory(): string
    {
        return $this->testDirectory;
    }

    public function validate(mixed $data, mixed $schema): ComplianceValidationResult
    {
        // @phpstan-ignore-next-line - Dynamic class instantiation with verified validator interface
        $validator = new ($this->validatorClass)($this->schemaLoader, $this->enableFormatValidation);
        // @phpstan-ignore-next-line - Schema is validated at runtime to be array<string, mixed>
        $result = $validator->validate($data, $schema);

        // @phpstan-ignore-next-line - Anonymous class with ValidationResult parameter
        return new readonly class($result) implements ComplianceValidationResult
        {
            public function __construct(
                private ValidationResult $result,
            ) {}

            public function isValid(): bool
            {
                return $this->result->valid;
            }

            public function getErrors(): array
            {
                // @phpstan-ignore-next-line - Returns ValidationError[] which is compatible with interface contract
                return $this->result->errors;
            }
        };
    }

    public function getTestFilePatterns(): array
    {
        return ['*.json', 'optional/*.json', 'optional/*/*.json'];
    }

    public function decodeJson(string $json): mixed
    {
        return JsonDecoder::decode($json);
    }

    public function shouldIncludeFile(string $filePath): bool
    {
        return array_all($this->excludePaths, fn ($excludePath): bool => !str_contains($filePath, (string) $excludePath));
    }
}
