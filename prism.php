<?php

declare(strict_types=1);

use Cline\JsonSchema\Adapters\ComplianceAdapter;
use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\Validators\Draft06Validator;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\Validators\Draft201909Validator;
use Cline\JsonSchema\Validators\Draft202012Validator;

$schemaLoader = new SchemaLoader(__DIR__.'/compliance/JSON-Schema-Test-Suite/remotes');

// Helper function to create both required and optional/format adapters for a draft
function createAdapters(string $name, string $validatorClass, string $basePath, SchemaLoader $schemaLoader): array
{
    $requiredDir = $basePath;
    $formatDir = $basePath.'/optional/format';

    $adapters = [
        // Required tests + optional non-format tests (format validation OFF)
        // Exclude optional/format directory to avoid double-counting
        new ComplianceAdapter(
            name: $name,
            validatorClass: $validatorClass,
            testDirectory: $requiredDir,
            schemaLoader: $schemaLoader,
            enableFormatValidation: false,
            excludePaths: ['/optional/format/'],
        ),
    ];

    // Optional format tests (format validation ON) - only if directory exists
    if (is_dir($formatDir)) {
        $adapters[] = new ComplianceAdapter(
            name: $name.' (format)',
            validatorClass: $validatorClass,
            testDirectory: $formatDir,
            schemaLoader: $schemaLoader,
            enableFormatValidation: true,
            excludePaths: [],
        );
    }

    return $adapters;
}

return [
    ...createAdapters('Draft 04', Draft04Validator::class, __DIR__.'/compliance/JSON-Schema-Test-Suite/tests/draft4', $schemaLoader),
    ...createAdapters('Draft 06', Draft06Validator::class, __DIR__.'/compliance/JSON-Schema-Test-Suite/tests/draft6', $schemaLoader),
    ...createAdapters('Draft 07', Draft07Validator::class, __DIR__.'/compliance/JSON-Schema-Test-Suite/tests/draft7', $schemaLoader),
    ...createAdapters('Draft 2019-09', Draft201909Validator::class, __DIR__.'/compliance/JSON-Schema-Test-Suite/tests/draft2019-09', $schemaLoader),
    ...createAdapters('Draft 2020-12', Draft202012Validator::class, __DIR__.'/compliance/JSON-Schema-Test-Suite/tests/draft2020-12', $schemaLoader),
];
